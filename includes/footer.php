    </main>
    <footer class="site-footer">
        <div class="container footer-bar">
            <div>
                <a class="footer-contact" href="contact.php">Contact Us</a>
                <p>FitTrack keeps workouts, meals, and runs in one focused place.</p>
            </div>
            <span>&copy; <?= date('Y') ?> FitTrack. All rights reserved.</span>
        </div>
    </footer>
</div>
<script src="assets/js/app.js?v=<?= e((string) filemtime(__DIR__ . '/../assets/js/app.js')) ?>"></script>
</body>
</html>
