<template>
    <div class="loginForm">
        {{ $t( errorMsg ) }}
    <form
            @submit.prevent="login"
            :class=" { loggingIn: loggingIn } ">
        <label>{{ $t( 'Username' ) }}<br>
            <input
                    class="input"
                    type="text"
                    v-model="ownUsername">
        </label>
        <br>
        <br>
        <label>{{ $t( 'Password' ) }}<br>
            <input
                    class="input"
                    type="password"
                    v-model="ownPassword">
        </label>
        <br>
        <br>
        <input
                class="btn"
                type="submit"
                :value=" $t( 'Login' ) "
                :disabled=" ! ownPassword || ! ownUsername ">
    </form>
    <LoaderAnimation
            v-if=" loggingIn "
            size="2em" />
        <br>
        <span v-html=" $t( 'Login as' ) "></span><br>
        Ala / Ala<br>
        Bartek / Bartek<br>
        Cecylia / Cecylia<br>
        Darek / Darek
</div>
</template>

<script>
    import LoaderAnimation from './LoaderAnimation.vue'

    export default {
        name: 'LoginForm',
        components: {
            LoaderAnimation,
        },
        props: {
            username: '',
            password: '',
            error: false,
            errorMsg: '',
            loggingIn: false,
        },
        data() {
            return {
                ownUsername: '',
                ownPassword: '',
            }
        },
        methods: {
            login() {
                this.$emit( 'login' , { username: this.ownUsername , password: this.ownPassword } );
            }
        },
        created() {
            this.ownUsername = this.username;
            this.ownPassword = this.password;
        },
        emits: [ 'login' ]
    }
</script>

