<script>
    export default {
        name: 'GeneralUsage',
        data() {
            return {
                theme: 'themeDark',
                tab: 'userRatings',
                desktop: false,
                loadingScreen: true,
            }
        },
        methods: {
            setView(e) {
                this.desktop = window.innerWidth > 800;
            },

            setTheme( theme ) {
                this.theme = theme;
                const d = new Date();
                d.setTime(d.getTime() + (1*24*60*60*1000));
                let expires = d.toUTCString();
                document.cookie = "ratifier_theme=" + theme + ";expires=" + expires + ";path=/";
            },

            setLanguage( lang ) {
                this.$i18n.locale = lang;
                const d = new Date();
                d.setTime(d.getTime() + (1*24*60*60*1000));
                let expires = d.toUTCString();
                document.cookie = "ratifier_lang=" + lang + ";expires=" + expires + ";path=/";
            }
        },
    }
</script>

