<div class="search-container">
    <form method="GET" action="">
        <input type="text" name="search" id="search" class="search-input" placeholder="Search by Student ID or Name" value="<?= htmlspecialchars($searchTerm) ?>" oninput="fetchSuggestions(this.value)">
        <button type="submit">Search</button>
        <span id="clear-search" class="clear-icon">&#x2715;</span>
    </form>
    <ul id="suggestions" style="display:none;"></ul>
</div>
