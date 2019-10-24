function showOptions() {
    document.getElementById("menuDropdown").classList.toggle("show");
}

function showOptions2() {
    document.getElementById("menuDropdown2").classList.toggle("show");
}

window.onclick = function(e) {
    if (!e.target.matches('.dropbtn')) {
        var menuDropdown = document.getElementById("menuDropdown");
        if (menuDropdown.classList.contains('show')) {
            menuDropdown.classList.remove('show');
        }
    }
    if (!e.target.matches('.dropbtn2')) {
        var menuDropdown = document.getElementById("menuDropdown2");
        if (menuDropdown.classList.contains('show')) {
            menuDropdown.classList.remove('show');
        }

    }
}