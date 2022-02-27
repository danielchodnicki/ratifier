<script>
    export default {
        name: 'UserManagement',
        data() {
            return {
                isLoggedIn: false,
                username: '',
                password: '',
                loginError: false,
                loginErrorMsg: '',
                isLoggingIn: false,
            }
        },
        methods: {

            logout() {
                this.isLoggedIn = false;
                this.username = '';
                this.password = '';
                this.ratifier.config.auth = '';
                this.ratifier.config.wpNonce = '';
                this.wpNonce = false;
                document.cookie = "ratifier_username=;expires=Thu, 01 Jan 1970 00:00:01 GMT;path=/";
                document.cookie = "ratifier_password=;expires=Thu, 01 Jan 1970 00:00:01 GMT;path=/";
                this.setDefaults();
            },

            async login( data ) {
                this.isLoggingIn = true;
                this.loginErrorMsg = '';
                this.loginError = false;
                let result = await this.ratifier.login( data.username , data.password );
                this.isLoggingIn = false;
                if ( result.success ) {
                    this.username = data.username;
                    this.password = data.password;
                    this.isLoggedIn = true;
                    const d = new Date();
                    d.setTime(d.getTime() + (1*24*60*60*1000));
                    let expires = d.toUTCString();
                    document.cookie = "ratifier_username=" + this.username + ";expires=" + expires + ";path=/";
                    document.cookie = "ratifier_password=" + this.password + ";expires=" + expires + ";path=/";
                }
                else {
                    this.loginError = result.code;
                    this.loginErrorMsg = 'Login error message';
                }
                this.loadingScreen = false;
            },

            tryLoggingIn() {
                let username = this.getCookie( 'ratifier_username' );
                let password = this.getCookie( 'ratifier_password' );
                if ( username && password ) {
                    this.username = username;
                    this.password = password;
                    this.login( { username , password } );
                }
                else {
                    this.loadingScreen = false;
                }
            },
        },
    }
</script>

