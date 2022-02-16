<template>
    <span
            class="signal"
            :style="[
                size ? { fontSize: size } : '' ,
                color ? { borderColor: color } : '',
                center ? { display: 'block' , margin: '.3em auto' } : ''
               ]">
        <span></span>
        <span></span>
    </span>
</template>

<script>
    export default {
        name: 'LoaderAnimation',
        props: [ 'size' , 'color' , 'center' ],
    }
</script>

