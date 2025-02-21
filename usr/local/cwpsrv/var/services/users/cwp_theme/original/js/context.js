// register context-menu-item
Vue.component('context-menu-item', {
    template: '#template-context-menu-item',
    props: {
        icon: ''
    }
});

// register context-menu
Vue.component('context-menu', {
    template: '#template-context-menu',
    props: {
        icon: ''
    },
    methods: {
        contextAction: function(action) {
            vm.contextAction(action);
        }
    }
});