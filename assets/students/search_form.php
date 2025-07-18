<div class="search-container" style="position: relative;">
    <form method="GET" action="">
        <input type="text" name="search" id="search" class="search-input" placeholder="Search by Student ID or Name" value="<?= htmlspecialchars($searchTerm) ?>" oninput="fetchSuggestions(this.value)">
        <button type="submit">Search</button>
        <span id="clear-search" class="clear-icon" onclick="clearSearch()">&
    </form>
    <ul id="suggestions" style="display:none;"></ul> <!-- Suggestions List -->
</div>

<script>
  function fetchSuggestions(query) {
    if (query.length === 0) {
        document.getElementById('suggestions').style.display = 'none';
        document.getElementById('clear-search').style.display = 'none';  
        return;
    }

    
    document.getElementById('clear-search').style.display = 'inline';  

    
    fetch('fetch_suggestions.php?q=' + query)
        .then(response => response.json())
        .then(data => {
            let suggestionsContainer = document.getElementById('suggestions');
            suggestionsContainer.innerHTML = ''; 
            suggestionsContainer.style.display = data.length > 0 ? 'block' : 'none'; 

            
            data.forEach(item => {
                let li = document.createElement('li');
                li.textContent = `${item.name} (${item.studentid})`;

                
                li.onclick = function() {
                    document.getElementById('search').value = item.name; 
                    document.getElementById('suggestions').style.display = 'none'; 

                    
                    document.querySelector('form').submit();
                };

                suggestionsContainer.appendChild(li); 
            });
        })
        .catch(error => console.error('Error fetching suggestions:', error));
}


function clearSearch() {
    document.getElementById('search').value = '';  
    document.getElementById('suggestions').style.display = 'none';  
    document.getElementById('clear-search').style.display = 'none';  

    
    
    window.location.href = window.location.pathname; 
}
</script>