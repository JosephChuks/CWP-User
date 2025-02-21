(function () {
    'use strict';
    var VueTreeItem = Vue.extend({
        template: '<li :class="{parent_li: node.isParent}" >' +
            '<i v-if="node.isParent" v-on:click="toggle(node)"class="fa icon-open-state" :class=\'{"fa-plus": !node.isOpen, "fa-minus": node.isOpen}\'></i>' +
            '<span @dragover.prevent @drop="draga(node)" @dragenter="dragEnter" @dragLeave="dragLeave">' +
            '<i v-if="showIcon(node)" :class="nodeClass(node)"></i> <a class="ml5" href="javascript:" v-on:click="listFil(node)">{{node.name}}</a></span>' +
            '<ul v-show="node.isOpen">' +
            '<li v-show="node.showLoading && node._loading"><i class="fa fa-spinner fa-pulse"></i></li>' +
            '<vue-tree-item v-for="item in node.children" :node="item"></vue-tree-item>' +
            '</ul>' +
            '</li>',
        props: {
            node: {
                type: Object
            }
        },
        methods: {
            showIcon: function (node) {
                return node.icon || node.openedIcon || node.closedIcon;
            },
            nodeClass: function (node) {
                if (node.isOpen) {
                    return node.openedIcon || node.icon;
                } else {
                    return node.closedIcon || node.icon;
                }
            },
            toggle: function (node) {
                vm.listSub(node);
               if (node.hasOwnProperty('isOpen')) {
                    node.isOpen = !node.isOpen;
                } else {
                    Vue.set(node, 'isOpen', true);

                }
            },
            dragEnter(ev){
                vm.dragEnter(ev);
            },
            dragLeave(ev){
                vm.dragLeave(ev);
            },
            draga:function(node){
                vm.dragado(node.id,'directory');
            },
            listFil: function(node){
                vm.listFiles(node.id);
                Vue.set(node, 'isOpen', true);
                vm.listSub(node,1);
            }
        },
        watch: {
            'node.isOpen': function (val) {
                if (!this.node.hasOwnProperty('_loading')) {
                    Vue.set(this.node, '_loading', false);
                }

                if (val) {
                    if (typeof this.node.onOpened === 'function') {
                        this.node.onOpened(this.node);
                    }
                } else {
                    if (typeof this.node.onClosed === 'function') {
                        this.node.onClosed(this.node);
                    }
                }
            }
        }
    });
    Vue.component('vue-tree-item', VueTreeItem);

    var VueTree = Vue.extend({
        template: '<div class="vue-tree"><ul>' +
            '<tree-item :node.sync="option.root"></tree-item>' +
            '</ul></div>',
        props: {
            option: {
                type: Object
            }
        },
        components: {
            'tree-item': VueTreeItem
        }
    });
    Vue.component('vue-tree', VueTree);
})();