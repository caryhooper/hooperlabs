<?php echo '<footer>
    <h3>
        Contact Us
    </h3>
    <p class="text">
        If you have any questions, suggestions, or concerns, please reach out on <a href="https://twitter.com/nopantrootdance">Twitter</a> (@nopantrootdance).  Feel free to contribute to any of the projects on <a href="https://www.github.com/caryhooper">GitHub</a>.
    </p>
    </br>
    </br>
    <p class="request_id">';
echo 'Request ID: '.hash('sha3-512',random_bytes(512));
?>

<?php echo '</p>
</footer>';
?>
