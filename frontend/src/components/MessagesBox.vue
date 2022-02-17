<template>
    <Transition
        name="fade"
        appear>
        <div
                v-if=" show "
                class="messagesBox">
            <div class="messagesBoxInner">
                <button
                        class="btnTransparent popupClose"
                        :title=" $t( 'Close pop-up' ) "
                        @click=" $emit( 'close-popup' ) ">
                    <font-awesome-icon
                        icon="times"></font-awesome-icon>
                </button>
                <h2
                        v-if=" title "
                        >{{ $t( title ) }} </h2>
                <div
                        v-html=" $t( message ) ">
                </div>
            </div>
        </div>
    </Transition>
</template>

<script>
    export default {
        name: 'MessagesBox',
        data() {
            return {
                show: false,
            }
        },
        props: [ 'title' , 'message' ],
        emits: [ 'closed-popup' ],
        mounted() {
            this.show = true;
        }
    }
</script>

