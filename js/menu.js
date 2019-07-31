function showOptions() {
    document.getElementById("menuDropdown").classList.toggle("show");
}
window.onclick = function(e) {
    if (!e.target.matches('.dropbtn')) {
        var menuDropdown = document.getElementById("menuDropdown");
        if (menuDropdown.classList.contains('show')) {
            menuDropdown.classList.remove('show');
        }

    }
}