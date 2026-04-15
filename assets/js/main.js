document.addEventListener('DOMContentLoaded', function() {
    // Гамбургер-меню
    const toggle = document.querySelector('.menu-toggle');
    if (toggle) {
        toggle.addEventListener('click', function() {
            document.querySelector('header nav').classList.toggle('active');
        });
    }

    // Лайки
    const likeBtns = document.querySelectorAll('.like-btn, .like-btn-detail');
    likeBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const projectId = this.dataset.projectId;
            const isLiked = this.classList.contains('liked');
            const action = isLiked ? 'unlike' : 'like';
            
            fetch('ajax/like.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `project_id=${projectId}&action=${action}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const likesSpan = this.parentElement.querySelector('.likes-count');
                    likesSpan.textContent = data.likes_count;
                    if (data.action === 'like') this.classList.add('liked');
                    else if (data.action === 'unlike') this.classList.remove('liked');
                } else {
                    alert(data.message || 'Ошибка');
                }
            })
            .catch(err => console.error(err));
        });
    });

    // Отправка отклика
    const submitBtn = document.getElementById('submit-response');
    if (submitBtn) {
        submitBtn.addEventListener('click', function() {
            const projectId = this.dataset.projectId;
            const message = document.getElementById('response-text').value.trim();
            const errorDiv = document.getElementById('response-error');
            if (!message) {
                errorDiv.textContent = 'Введите текст отклика';
                errorDiv.style.display = 'block';
                return;
            }
            errorDiv.style.display = 'none';
            
            fetch('/ajax/add_response.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `project_id=${projectId}&message=${encodeURIComponent(message)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const responsesList = document.getElementById('responses-list');
                    const newResponse = document.createElement('div');
                    newResponse.className = 'response-item';
                    newResponse.innerHTML = `
                        <div class="response-header">
                            <strong>${escapeHtml(data.response.executor_name)}</strong>
                            <span>${escapeHtml(data.response.created_at)}</span>
                        </div>
                        <div class="response-message">${data.response.message}</div>
                    `;
                    responsesList.prepend(newResponse);
                    document.getElementById('response-text').value = '';
                    const header = document.querySelector('.responses-section h3');
                    if (header) {
                        let count = parseInt(header.textContent.match(/\d+/)[0]) || 0;
                        header.textContent = `Отклики исполнителей (${count + 1})`;
                    }
                } else {
                    errorDiv.textContent = data.message;
                    errorDiv.style.display = 'block';
                }
            })
            .catch(err => {
                errorDiv.textContent = 'Ошибка отправки';
                errorDiv.style.display = 'block';
            });
        });
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }
});