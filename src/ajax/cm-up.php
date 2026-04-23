<?php
class CommentSystem {
    private $conn;
    private $anime_id;
    private $episode_id;
    private $user_id;

    public function __construct($conn, $episode_id, $anime_id) {
        $this->conn = $conn;
        $this->episode_id = (int)$episode_id;
        $this->anime_id = $anime_id;
        $this->user_id = isset($_COOKIE['userID']) ? (int)$_COOKIE['userID'] : null;
        
        error_log("CommentSystem initialized with: " . json_encode([
            'episode_id' => $this->episode_id,
            'anime_id' => $this->anime_id,
            'user_id' => $this->user_id
        ]));
    }

    public function getComments($page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        
        $query = "
            SELECT 
                c.*,
                (SELECT COUNT(*) FROM comment_reactions cr WHERE cr.comment_id = c.id AND cr.type = 1) as likes,
                (SELECT COUNT(*) FROM comment_reactions cr WHERE cr.comment_id = c.id AND cr.type = 0) as dislikes
            FROM comments c
            WHERE c.anime_id = ? 
            AND c.episode_id = ?
            AND c.parent_id IS NULL
            ORDER BY c.created_at DESC
            LIMIT ? OFFSET ?
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("siii", 
            $this->anime_id, 
            $this->episode_id, 
            $limit, 
            $offset
        );
        
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    private function getReplies($parent_id) {
        $query = "
            SELECT 
                c.*,
                u.username,
                COALESCE(u.image, u.avatar_url) as user_avatar,
                (SELECT COUNT(*) FROM comment_reactions cr WHERE cr.comment_id = c.id AND cr.type = 1) as likes,
                (SELECT COUNT(*) FROM comment_reactions cr WHERE cr.comment_id = c.id AND cr.type = 0) as dislikes
            FROM comments c
            LEFT JOIN users u ON c.user_id = u.id
            WHERE c.parent_id = ?
            ORDER BY c.created_at ASC
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $parent_id);
        $stmt->execute();
        $replies = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        foreach ($replies as &$reply) {
            $reply['userReaction'] = $this->getUserReaction($reply['id']);
        }

        return $replies;
    }

    public function addComment($content, $username, $avatar_url) {
        try {
            if (empty($this->anime_id)) {
                error_log("Error: anime_id is empty in addComment");
                return ['success' => false, 'message' => 'Invalid anime ID'];
            }

            $user_id = isset($_COOKIE['userID']) ? (int)$_COOKIE['userID'] : 0;
            
            $stmt = $this->conn->prepare("
                INSERT INTO comments 
                (content, username, user_avatar, episode_id, anime_id, user_id, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            if (!$stmt) {
                error_log("MySQL Prepare Error: " . $this->conn->error);
                return ['success' => false, 'message' => 'Failed to prepare statement'];
            }
            
            $stmt->bind_param("sssisi", 
                $content, 
                $username, 
                $avatar_url, 
                $this->episode_id, 
                $this->anime_id,
                $user_id
            );

            if ($stmt->execute()) {
                $comment_id = $this->conn->insert_id;
                return [
                    'success' => true,
                    'message' => 'Comment added successfully',
                    'comment' => [
                        'id' => $comment_id,
                        'content' => $content,
                        'username' => $username,
                        'user_avatar' => $avatar_url,
                        'episode_id' => $this->episode_id,
                        'anime_id' => $this->anime_id,
                        'created_at' => date('Y-m-d H:i:s'),
                        'likes' => 0,
                        'dislikes' => 0
                    ]
                ];
            } else {
                error_log("MySQL Error in addComment: " . $stmt->error);
                return ['success' => false, 'message' => 'Failed to add comment'];
            }
        } catch (Exception $e) {
            error_log("Exception in addComment: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error processing comment'];
        }
    }

    private function getCommentById($comment_id) {
        $query = "
            SELECT 
                c.*,
                u.username,
                COALESCE(u.image, u.avatar_url) as user_avatar
            FROM comments c
            LEFT JOIN users u ON c.user_id = u.id
            WHERE c.id = ?
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $comment_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function addReaction($comment_id, $type) {
        if (!$this->user_id) {
            return ['success' => false, 'message' => 'User not logged in'];
        }

        try {
            $this->conn->begin_transaction();

            // Check if user already reacted
            $stmt = $this->conn->prepare("
                SELECT type FROM comment_reactions 
                WHERE comment_id = ? AND user_id = ?
            ");
            $stmt->bind_param("ii", $comment_id, $this->user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $existing = $result->fetch_assoc();

            if ($existing) {
                if ($existing['type'] == $type) {
                    // Remove reaction if clicking the same button
                    $stmt = $this->conn->prepare("
                        DELETE FROM comment_reactions 
                        WHERE comment_id = ? AND user_id = ?
                    ");
                    $stmt->bind_param("ii", $comment_id, $this->user_id);
                    $stmt->execute();
                } else {
                    // Update reaction if different type
                    $stmt = $this->conn->prepare("
                        UPDATE comment_reactions 
                        SET type = ? 
                        WHERE comment_id = ? AND user_id = ?
                    ");
                    $stmt->bind_param("iii", $type, $comment_id, $this->user_id);
                    $stmt->execute();
                }
            } else {
                // Add new reaction
                $stmt = $this->conn->prepare("
                    INSERT INTO comment_reactions (comment_id, user_id, type) 
                    VALUES (?, ?, ?)
                ");
                $stmt->bind_param("iii", $comment_id, $this->user_id, $type);
                $stmt->execute();
            }

            $this->conn->commit();

            // Get updated counts
            $likes = $this->getReactionCount($comment_id, 1);
            $dislikes = $this->getReactionCount($comment_id, 0);
            $userReaction = $this->getUserReaction($comment_id);

            return [
                'success' => true,
                'likes' => $likes,
                'dislikes' => $dislikes,
                'userReaction' => $userReaction
            ];
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Reaction error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update reaction'];
        }
    }

    private function getReactionCount($comment_id, $type) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count 
            FROM comment_reactions 
            WHERE comment_id = ? AND type = ?
        ");
        $stmt->bind_param("ii", $comment_id, $type);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['count'];
    }

    private function getUserReaction($comment_id) {
        if (!$this->user_id) return null;
        
        $stmt = $this->conn->prepare("
            SELECT type 
            FROM comment_reactions 
            WHERE comment_id = ? AND user_id = ?
        ");
        $stmt->bind_param("ii", $comment_id, $this->user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0 ? $result->fetch_assoc()['type'] : null;
    }
}
?> 