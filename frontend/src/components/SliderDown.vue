<script>
    export default {
        name: 'SliderDown',
        data() {
            return {
                sliderDownTime: 1000,
            }
        },
        methods: {
            SliderDownEnter( el , done ) {
                done();
            },
            async SliderDownAfterEnter( el ) {
                el.style.height = el.children[0].clientHeight + 'px';
                el.children[0].style.opacity = 1;
                await this.sliderDownDelay(1000);
                el.children[0].style.position = 'static';
                el.style.height = 'auto';
            },
            async SliderDownBeforeLeave(el) {
                el.style.height = el.children[0].clientHeight + 'px';
                await this.sliderDownDelay(1);
                el.children[0].style.opacity = 0;
                el.style.height = '0px';
                await this.sliderDownDelay(1000);
            },
            async SlideDownLeave( el , done ) {
                el.style.height = el.children[0].clientHeight + 'px';
                await this.sliderDownDelay(1);
                el.style.height = '0px';
                await this.sliderDownDelay(1000);
                done();
            },
            sliderDownDelay(ms) {
                return new Promise(res => setTimeout(res, ms));
            }
        },
    }
</script>

