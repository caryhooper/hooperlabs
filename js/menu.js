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
//adding event listeners here to comply with CSP and prevent inline scripts.
document.addEventListener('DOMContentLoaded', function(){
    document.getElementById("b2r-button").addEventListener('click',showOptions);
    document.getElementById("mobile-button").addEventListener('click',showOptions2);
});
