// Make showToast globally available
window.showToast = function(title, message, type) {
    swal({
        title: title,
        text: message,
        icon: type,
        button: "Close",
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const filterBtn = document.getElementById('genreFilterBtn');
    const filterDropdown = document.getElementById('genreFilterDropdown');
    const searchForm = document.getElementById('search-form');
    const selectedGenres = new Set();
    
    if (filterBtn && filterDropdown) {
        filterBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            filterDropdown.style.display = filterDropdown.style.display === 'none' ? 'block' : 'none';
            filterBtn.classList.toggle('active');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!filterDropdown.contains(e.target) && e.target !== filterBtn) {
                filterDropdown.style.display = 'none';
                filterBtn.classList.remove('active');
            }
        });

        // Handle genre selection
        const genreItems = document.querySelectorAll('.genre-item');
        const selectedGenresContainer = document.getElementById('selectedGenres');

        function updateSelectedGenres() {
            selectedGenresContainer.innerHTML = Array.from(selectedGenres)
                .map(genre => `
                    <span class="genre-tag">
                        ${genre}
                        <span class="remove" data-genre="${genre}">×</span>
                    </span>
                `).join('');
        }

        genreItems.forEach(item => {
            item.addEventListener('click', (e) => {
                e.stopPropagation();
                const genre = item.textContent;
                if (selectedGenres.has(genre)) {
                    selectedGenres.delete(genre);
                    item.classList.remove('selected');
                } else {
                    selectedGenres.add(genre);
                    item.classList.add('selected');
                }
                updateSelectedGenres();
            });
        });

        selectedGenresContainer.addEventListener('click', (e) => {
            if (e.target.classList.contains('remove')) {
                const genre = e.target.dataset.genre;
                selectedGenres.delete(genre);
                document.querySelector(`.genre-item[data-genre="${genre.toLowerCase()}"]`)
                    .classList.remove('selected');
                updateSelectedGenres();
            }
        });

        // Modify form submission to include selected genres
        searchForm.addEventListener('submit', function(e) {
            if (selectedGenres.size > 0) {
                selectedGenres.forEach(genre => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'genre[]';
                    input.value = genre;
                    searchForm.appendChild(input);
                });
            }
        });
    }
});