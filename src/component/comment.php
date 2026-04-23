<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/_config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/src/ajax/cm-up.php');

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (isset($commentData)) {
    error_log('Comment Data Received: ' . print_r($commentData, true));
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if (!isset($_COOKIE['userID']) && $_POST['action'] !== 'get') {
        echo json_encode(['success' => false, 'message' => 'Please login to comment']);
        exit;
    }

    if (!isset($_POST['anime_id'])) {
        echo json_encode(['success' => false, 'message' => 'Missing anime ID']);
        exit;
    }
    
    switch ($_POST['action']) {
        case 'get':
            if (!isset($_POST['episode_id'])) {
                echo json_encode(['success' => false, 'message' => 'Missing episode ID']);
                exit;
            }
            
            $commentSystem = new CommentSystem($conn, $_POST['episode_id'], $_POST['anime_id']);
            $comments = $commentSystem->getComments();
            
            echo json_encode([
                'success' => true, 
                'comments' => $comments,
                'commentCount' => count($comments)
            ]);
            break;
            
        case 'add':
            if (!isset($_POST['content']) || empty(trim($_POST['content']))) {
                echo json_encode(['success' => false, 'message' => 'Comment content cannot be empty']);
                exit;
            }

            if (!isset($_POST['anime_id']) || empty($_POST['anime_id'])) {
                echo json_encode(['success' => false, 'message' => 'Missing anime ID']);
                exit;
            }
            
            $stmt = $conn->prepare("SELECT username, image FROM users WHERE id = ?");
            if (!$stmt) {
                error_log("MySQL Prepare Error: " . $conn->error);
                echo json_encode(['success' => false, 'message' => 'Failed to prepare statement: ' . $conn->error]);
                exit;
            }
            $stmt->bind_param("i", $_COOKIE['userID']);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
   
            $avatar_url = !empty($user['image']) ? $user['image'] : '';
            
            $commentSystem = new CommentSystem(
                $conn, 
                $_POST['episode_id'], 
                $_POST['anime_id'] 
            );
            
            $result = $commentSystem->addComment($_POST['content'], $user['username'], $avatar_url);
            
            
            echo json_encode($result);
            break;
            
        case 'react':
            if (!isset($_POST['comment_id']) || !isset($_POST['type'])) {
                echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
                exit;
            }
            
            $commentSystem = new CommentSystem($conn, $_POST['episode_id'], $_POST['anime_id']);
            $result = $commentSystem->addReaction($_POST['comment_id'], $_POST['type']);
            
            echo json_encode($result);
            break;
    }
    exit;
}

$user_id = isset($_COOKIE['userID']) ? $_COOKIE['userID'] : null;
$username = '';

if ($user_id) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $username = $user['username'] ?? '';
}
?>

<style>
</style>

<section class="block_area block_area-comment" id="comment-block">
    <script>var userId = <?php echo $user_id ?? 'null'; ?>;</script>
    <script>var is_logged_in = <?php echo $user_id ? '1' : '0'; ?>;</script>

    <div class="block_area-header block_area-header-tabs">
        <div class="float-left bah-heading mr-4">
            <h2 class="cat-heading">Comments</h2>
        </div>
        <div class="clearfix"></div>
    </div>
    
    <div class="show-comments">
        <div id="content-comments" class="comments-wrap">
            <div class="sc-header">
                <div class="sc-h-from">
                    <a class="btn btn-sm" data-toggle="dropdown" aria-haspopup="true" name="Ep_Number" aria-expanded="false">
                        Episode <span class="current-episode">1</span>
                        <i class="fas fa-angle-down ml-2"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-model dropdown-menu-normal" aria-labelledby="ssc-list">
                        <a class="dropdown-item cm-by active" data-value="episode" href="javascript:;">Episode <span class="current-episode">1</span> <i class="fas fa-check mt-2"></i></a>
                    </div>
                </div>

                <div class="sc-h-title">
                    <i class="far fa-comment-alt mr-2"></i>
                    <span id="comment-count">0</span> Comments
                </div>

                <div class="sc-h-sort">
                    <a class="btn btn-sm" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Sort by<i class="fas fa-sort ml-2"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-model dropdown-menu-normal" aria-labelledby="ssc-list">
                        <a class="dropdown-item cm-sort active" data-value="newest" href="javascript:;">Newest <i class="fas fa-check mt-2"></i></a>
                    </div>
                </div>
                <div class="clearfix"></div>
            </div>

            <div class="comment-input">
                <?php if ($user_id): ?>
                    <div class="user-avatar">
                        <img class="user-avatar-img" src="<?= htmlspecialchars($user['image']) ?>" alt="<?= htmlspecialchars($username) ?>">
                    </div>
                    <div class="ci-form">
                        <div class="user-name">
                            Comment as <span class="link-highlight ml-1"><?= htmlspecialchars($username) ?></span>
                        </div>
                        <form id="comment-form" class="preform preform-dark comment-form">
                            <div class="loading-absolute bg-white" id="comment-loading" style="display: none;">
                                <div class="loading">
                                    <div class="span1"></div>
                                    <div class="span2"></div>
                                    <div class="span3"></div>
                                </div>
                            </div>
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="episode_id" value="<?= htmlspecialchars($commentData['episode_id']) ?>" data-current-episode>
                            <input type="hidden" name="anime_id" value="<?= htmlspecialchars($commentData['anime_id']) ?>" data-current-anime>
                            <textarea class="form-control form-control-textarea comment-subject cm-input-base" 
                                    id="df-cm-content" 
                                    name="content" 
                                    placeholder="Leave a comment..."></textarea>
                            
                            <div class="ci-buttons" id="df-cm-buttons">
                                <div class="ci-b-right">
                                    <div class="cb-li">
                                        <a class="btn btn-sm btn-secondary" id="df-cm-close">Close</a>
                                    </div>
                                    <div class="cb-li">
                                        <button type="submit" class="btn btn-sm btn-primary ml-2">Comment</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="login-prompt">
                        <p>Please <a href="/login">login</a> to comment</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="list-comment">
                <div class="cw_list" id="comments-list">
                    <div class="comment">
                        <div class="comment-avatar">
                            <img class="comment-avatar-img" src="/public/images/no-avatar.jpeg" alt="username">
                        </div>
                        <div class="comment-body">
                            <div class="comment-info">
                                <span class="comment-user">Username</span>
                                <span class="comment-time">2 hours ago</span>
                            </div>
                            <div class="comment-content">
                                This is a sample comment.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentEpisode = '1';
    let currentAnimeId = '';

    async function loadComments(episodeId, animeId) {
        try {
            console.log('Loading comments for:', { episodeId, animeId }); 

            document.querySelectorAll('[data-current-episode]').forEach(input => {
                input.value = episodeId;
            });
            document.querySelectorAll('[data-current-anime]').forEach(input => {
                input.value = animeId;
            });

            const formData = new FormData();
            formData.append('action', 'get');
            formData.append('episode_id', episodeId);
            formData.append('anime_id', animeId);

            const response = await fetch('/src/component/comment.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            
            if (result.success) {
                document.getElementById('comment-count').textContent = result.commentCount;

                const commentsList = document.getElementById('comments-list');
                commentsList.innerHTML = result.comments.map(comment => `
                    <div class="cw_l-line" id="cm-${comment.id}">
                        <a href="/community/user/${comment.user_id}" target="_blank" class="user-avatar" onclick="alert('Maybe in future, a detailed user profile will be added!')">
                            <img class="user-avatar-img" src="${comment.user_avatar}" alt="${comment.username}">
                        </a>
                        <div class="info">
                            <div class="ihead">
                                <a href="/community/user/${comment.user_id}" target="_blank" class="user-name" onclick="alert('Maybe in future, a detailed user profile will be added!')">${comment.username}</a>
                                <div class="time">${comment.created_at}</div>
                            </div>
                            <div class="ibody">
                                <p>${comment.content}</p>
                            </div>
                            <div class="ibottom">
                                <div class="ib-li ib-like">
                                    <a class="btn cm-btn-vote" data-id="${comment.id}" data-type="1">
                                        <i class="far fa-thumbs-up mr-1"></i>
                                        <span class="value">${comment.likes || ''}</span>
                                    </a>
                                </div>
                                <div class="ib-li ib-dislike">
                                    <a class="btn cm-btn-vote" data-id="${comment.id}" data-type="0">
                                        <i class="far fa-thumbs-down mr-1"></i>
                                        <span class="value">${comment.dislikes || ''}</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                `).join('');

                document.querySelectorAll('.current-episode').forEach(el => {
                    el.textContent = episodeId;
                });
                document.querySelectorAll('input[name="episode_id"]').forEach(input => {
                    input.value = episodeId;
                });
                document.querySelectorAll('input[name="anime_id"]').forEach(input => {
                    input.value = animeId;
                });
            }
        } catch (error) {
            console.error('Error loading comments:', error);
        }
    }

    function initializeComments() {
        const urlParams = new URLSearchParams(window.location.search);
        currentEpisode = urlParams.get('ep') || '1';
        currentAnimeId = window.location.pathname.split('/').pop().split('?')[0];
        
        console.log('Initializing comments with:', { currentEpisode, currentAnimeId }); 
        
        if (currentAnimeId) {
            loadComments(currentEpisode, currentAnimeId);
        }
    }

    window.addEventListener('episodeChange', function(e) {
        console.log('Episode change event received:', e.detail); 
        
        if (e.detail && e.detail.episodeNumber) {
            currentEpisode = e.detail.episodeNumber;
            if (currentAnimeId) {
                loadComments(currentEpisode, currentAnimeId);
            }
        }
    });

    let lastUrl = location.href; 
    new MutationObserver(() => {
        const url = location.href;
        if (url !== lastUrl) {
            lastUrl = url;
            initializeComments();
        }
    }).observe(document, {subtree: true, childList: true});

    initializeComments();

    const commentForm = document.getElementById('comment-form');
    if (commentForm) {
        commentForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const episodeId = this.querySelector('[name="episode_id"]').value;
            const animeId = this.querySelector('[name="anime_id"]').value;
            
            if (!episodeId || !animeId) {
                alert('Missing episode or anime information');
                return;
            }

            const formData = new FormData(this);
            formData.set('episode_id', episodeId);
            formData.set('anime_id', animeId);
            
            try {
                document.getElementById('comment-loading').style.display = 'block';
                
                const response = await fetch('/src/component/comment.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                if (result.success) {
                    this.querySelector('textarea').value = '';
                    loadComments(episodeId, animeId);
                } else {
                    alert(result.message || 'Error submitting comment');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to submit comment');
            } finally {
                document.getElementById('comment-loading').style.display = 'none';
            }
        });
    }
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('click', async function(e) {
        const voteButton = e.target.closest('.cm-btn-vote');
        if (!voteButton) return;
        
        if (!userId) {
            if (typeof openModal === 'function') {
                openModal();
            } else {
                alert('Please login to react to comments');
            }
            return;
        }
        const commentId = voteButton.dataset.id;
        const type = voteButton.dataset.type;
        const episodeId = document.querySelector('input[name="episode_id"]').value;
        const animeId = document.querySelector('input[name="anime_id"]').value;
        try {
            const formData = new FormData();
            formData.append('action', 'react');
            formData.append('comment_id', commentId);
            formData.append('type', type);
            formData.append('episode_id', episodeId);
            formData.append('anime_id', animeId);
            const response = await fetch('/src/component/comment.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                const commentElement = document.querySelector(`#cm-${commentId}`);
                const likeBtn = commentElement.querySelector(`.cm-btn-vote[data-type="1"]`);
                const dislikeBtn = commentElement.querySelector(`.cm-btn-vote[data-type="0"]`);
                likeBtn.querySelector('.value').textContent = result.likes || '';
                dislikeBtn.querySelector('.value').textContent = result.dislikes || '';               
                likeBtn.classList.toggle('active', result.userReaction === 1);
                dislikeBtn.classList.toggle('active', result.userReaction === 0);
            }
        } catch (error) {
            console.error('Error handling reaction:', error);
        }
    });
});
</script>
