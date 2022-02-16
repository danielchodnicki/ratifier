<script>
    export default {
        name: 'GeneralUsage',
        methods: {
            getCookie(cookieName) {
                let cookie = {};
                document.cookie.split(';').forEach(function(el) {
                    let [key,value] = el.split('=');
                    cookie[key.trim()] = value;
                });
                return cookie[cookieName];
            },
        },
    }
</script>

