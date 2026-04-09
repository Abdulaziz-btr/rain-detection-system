        </div><!-- end content -->
    </div><!-- end main -->

    <script>
        // Update last update time
        function updateTime() {
            const now = new Date();
            const h = now.getHours().toString().padStart(2,'0');
            const m = now.getMinutes().toString().padStart(2,'0');
            const s = now.getSeconds().toString().padStart(2,'0');
            const ampm = h >= 12 ? 'PM' : 'AM';
            const h12 = (h % 12 || 12).toString().padStart(2,'0');
            const el = document.getElementById('lastUpdateTime');
            if (el) el.textContent = h12 + ':' + m + ':' + s + ' ' + ampm;
        }
        updateTime();
        setInterval(updateTime, 1000);
    </script>
</body>
</html>
