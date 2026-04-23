class CommentHandler {
    constructor() {
        this.page = 1;
        this.loading = false;
        this.initializeEventListeners();
        this.initializeEmojiPickers();
    }

    initializeEmojiPickers() {
        document.addEventListener('click', (e) => {
            if (e.target.closest('.emoji-picker-btn')) {
                const button = e.target.closest('.emoji-picker-btn');
                const textarea = button.closest('.comment-input-wrapper').querySelector('textarea');
                
                // Remove any existing picker
                document.querySelectorAll('.emoji-mart').forEach(picker => picker.remove());
                
                // Create new picker
                const picker = new EmojiMart.Picker({
                    onSelect: emoji => {
                        const cursorPos = textarea.selectionStart;
                        const text = textarea.value;
                        textarea.value = text.slice(0, cursorPos) + emoji.native + text.slice(cursorPos);
                        picker.remove();
                    },
                    set: 'twitter',
                    showPreview: false,
                    showSkinTones: false,
                    style: {
                        position: 'absolute',
                        right: '0',
                        bottom: '100%'
                    }
                });
                
                button.parentNode.appendChild(picker);
                
                // Close picker when clicking outside
                document.addEventListener('click', (event) => {
                    if (!event.target.closest('.emoji-mart') && !event.target.closest('.emoji-picker-btn')) {
                        picker.remove();
                    }
                });
            }
        });
    }

    // Add this method to handle reactions
    async handleReaction(button) {
        if (!this.userId) {
            alert('Please login to react to comments');
            return;
        }

        const commentId = button.dataset.id;
        const type = button.dataset.type;
        const formData = new FormData();
        formData.append('action', 'react');
        formData.append('comment_id', commentId);
        formData.append('type', type);

        try {
            const response = await fetch('/src/component/comment.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            if (result.success) {
                const comment = document.querySelector(`#comment-${commentId}`);
                const likeBtn = comment.querySelector(`.cm-btn-vote[data-type="1"]`);
                const dislikeBtn = comment.querySelector(`.cm-btn-vote[data-type="0"]`);

                likeBtn.querySelector('.value').textContent = result.likes;
                dislikeBtn.querySelector('.value').textContent = result.dislikes;

                likeBtn.classList.toggle('active', result.userReaction === 1);
                dislikeBtn.classList.toggle('active', result.userReaction === 0);
            }
        } catch (error) {
            console.error('Error handling reaction:', error);
        }
    }

    initializeEventListeners() {
        // Comment form submission
        document.getElementById('comment-form')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.submitComment(e.target);
        });

        // Reply button clicks
        document.addEventListener('click', (e) => {
            if (e.target.closest('.ib-reply .btn')) {
                const commentId = e.target.closest('.ib-reply').dataset.id;
                this.toggleReplyForm(commentId);
            }
        });

        // Reply form submission
        document.addEventListener('submit', (e) => {
            if (e.target.classList.contains('reply-form')) {
                e.preventDefault();
                this.submitReply(e.target);
            }
        });

        // Close reply form
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('btn-close-reply')) {
                const replyBlock = e.target.closest('.reply-block');
                if (replyBlock) {
                    replyBlock.style.display = 'none';
                }
            }
        });

        // Reaction buttons
        document.addEventListener('click', (e) => {
            const reactionBtn = e.target.closest('.cm-btn-vote');
            if (reactionBtn) {
                e.preventDefault();
                this.handleReaction(reactionBtn);
            }
        });
    }

    toggleReplyForm(commentId) {
        const replyBlock = document.querySelector(`#reply-${commentId}`);
        if (replyBlock) {
            replyBlock.style.display = replyBlock.style.display === 'none' ? 'block' : 'none';
        }
    }

    async submitReply(form) {
        if (this.loading) return;
        
        try {
            this.loading = true;
            const formData = new FormData(form);
            formData.append('action', 'reply');

            const response = await fetch('/src/component/comment.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            if (data.success) {
                // Add reply to DOM
                const parentId = formData.get('parent_id');
                const repliesContainer = document.querySelector(`#replies-${parentId}`);
                if (repliesContainer) {
                    repliesContainer.insertAdjacentHTML('beforeend', this.generateCommentHTML(data.comment));
                }
                
                // Clear and hide form
                form.reset();
                form.closest('.reply-block').style.display = 'none';
            } else {
                alert(data.message || 'Error submitting reply');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Failed to submit reply');
        } finally {
            this.loading = false;
        }
    }

    async submitComment(form) {
        try {
            const formData = new FormData(form);
            formData.append('action', 'add');
            
            // Debug log the form data
            console.log('Submitting comment with data:', Object.fromEntries(formData));

            const response = await fetch('/src/component/comment.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            if (data.success) {
                this.addCommentToDOM(data.comment);
                form.reset();
            } else {
                console.error('Comment submission error:', data);
                alert(data.message || 'Error submitting comment');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Failed to submit comment');
        }
    }

    async loadMoreComments() {
        if (this.loading) return;
        
        try {
            this.loading = true;
            const episodeId = document.querySelector('[name="episode_id"]').value;
            
            const response = await fetch('/src/component/comment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get&episode_id=${episodeId}&page=${++this.page}`
            });

            const data = await response.json();
            if (data.success) {
                this.appendComments(data.comments);
            }
        } catch (error) {
            console.error('Error:', error);
        } finally {
            this.loading = false;
        }
    }

    addCommentToDOM(comment) {
        const container = document.getElementById('comments-container');
        container.insertAdjacentHTML('afterbegin', this.generateCommentHTML(comment));
    }

    appendComments(comments) {
        const container = document.getElementById('comments-container');
        comments.forEach(comment => {
            container.insertAdjacentHTML('beforeend', this.generateCommentHTML(comment));
        });
    }

    generateCommentHTML(comment) {
        return `
            <div class="comment" data-id="${comment.id}">
                <div class="comment-avatar">
                    <img src="${comment.avatar_url}" alt="${comment.username}">
                </div>
                <div class="comment-content">
                    <div class="comment-header">
                        <span class="username">${comment.username}</span>
                        <span class="timestamp">${new Date(comment.created_at).toLocaleString()}</span>
                    </div>
                    <div class="comment-text">${comment.content}</div>
                    <div class="comment-actions">
                        <button class="reply-btn">Reply</button>
                        <span class="likes">${comment.like_count} likes</span>
                        <span class="dislikes">${comment.dislike_count} dislikes</span>
                    </div>
                </div>
            </div>
        `;
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new CommentHandler();
});