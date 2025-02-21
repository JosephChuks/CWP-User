Dropzone.autoDiscover = false;
var vueDropzone = Vue.component('vuedropzone',{
    template: '<div :id="id" ref="dropzoneElement" :class="{ \'vue-dropzone dropzone\': includeStyling }">' +
        '<div v-if="useCustomSlot" class="dz-message">' +
        '<slot> <h3 class="dropzone-custom-title">Drag and drop to upload content!</h3>' +
        '<div class="subtitle">...or click to select a file from your computer</div></slot>' +
        '</div>' +
        '</div>',

    props: {
        id: {
            type: String,
            required: true,
            default: "dropzone"
        },
        options: {
            type: Object,
            required: true
        },
        includeStyling: {
            type: Boolean,
            default: true,
            required: false
        },
        awss3: {
            type: Object,
            required: false,
            default: null
        },
        destroyDropzone: {
            type: Boolean,
            default: true,
            required: false
        },
        duplicateCheck: {
            type: Boolean,
            default: false,
            required: false
        },
        useCustomSlot: {
            type: Boolean,
            default: false,
            required: false
        }
    },
    data() {
        return {
            isS3: false,
            isS3OverridesServerPropagation: false,
            wasQueueAutoProcess: true
        };
    },
    computed: {
        dropzoneSettings() {
            let defaultValues = {
                thumbnailWidth: 200,
                thumbnailHeight: 200
            };
            Object.keys(this.options).forEach(function (key) {
                defaultValues[key] = this.options[key];
            }, this);

            return defaultValues;
        }
    },
    mounted() {
        if (this.$isServer && this.hasBeenMounted) {
            return;
        }
        this.hasBeenMounted = true;

        this.dropzone = new Dropzone(
            this.$refs.dropzoneElement,
            this.dropzoneSettings
        );

        let vm = this;

        this.dropzone.on("thumbnail", function (file, dataUrl) {
            vm.$emit("vdropzone-thumbnail", file, dataUrl);
        });

        this.dropzone.on("addedfile", function (file) {
            var isDuplicate = false;
            if (vm.duplicateCheck) {
                if (this.files.length) {
                    var _i, _len;
                    for (
                        _i = 0, _len = this.files.length;
                        _i < _len - 1;
                        _i++ // -1 to exclude current file
                    ) {
                        if (
                            this.files[_i].name === file.name &&
                            this.files[_i].size === file.size &&
                            this.files[_i].lastModifiedDate.toString() ===
                            file.lastModifiedDate.toString()
                        ) {
                            this.removeFile(file);
                            isDuplicate = true;
                            vm.$emit("vdropzone-duplicate-file", file);
                        }
                    }
                }
            }

            vm.$emit("vdropzone-file-added", file);

        });

        this.dropzone.on("addedfiles", function (files) {
            vm.$emit("vdropzone-files-added", files);
        });

        this.dropzone.on("removedfile", function (file) {
            vm.$emit("vdropzone-removed-file", file);
            if (file.manuallyAdded && vm.dropzone.options.maxFiles !== null)
                vm.dropzone.options.maxFiles++;
        });

        this.dropzone.on("success", function (file, response) {
            vm.$emit("vdropzone-success", file, response);

        });

        this.dropzone.on("successmultiple", function (file, response) {
            vm.$emit("vdropzone-success-multiple", file, response);
        });

        this.dropzone.on("error", function (file, message, xhr) {
            vm.$emit("vdropzone-error", file, message, xhr);
            if (this.isS3) vm.$emit("vdropzone-s3-upload-error");
        });

        this.dropzone.on("errormultiple", function (files, message, xhr) {
            vm.$emit("vdropzone-error-multiple", files, message, xhr);
        });

        this.dropzone.on("sending", function (file, xhr, formData) {
            vm.$emit("vdropzone-sending", file, xhr, formData);
        });

        this.dropzone.on("sendingmultiple", function (file, xhr, formData) {
            vm.$emit("vdropzone-sending-multiple", file, xhr, formData);
        });

        this.dropzone.on("complete", function (file) {
            vm.$emit("vdropzone-complete", file);
        });

        this.dropzone.on("completemultiple", function (files) {
            vm.$emit("vdropzone-complete-multiple", files);
        });

        this.dropzone.on("canceled", function (file) {
            vm.$emit("vdropzone-canceled", file);
        });

        this.dropzone.on("canceledmultiple", function (files) {
            vm.$emit("vdropzone-canceled-multiple", files);
        });

        this.dropzone.on("maxfilesreached", function (files) {
            vm.$emit("vdropzone-max-files-reached", files);
        });

        this.dropzone.on("maxfilesexceeded", function (file) {
            vm.$emit("vdropzone-max-files-exceeded", file);
        });

        this.dropzone.on("processing", function (file) {
            vm.$emit("vdropzone-processing", file);
        });

        this.dropzone.on("processingmultiple", function (files) {
            vm.$emit("vdropzone-processing-multiple", files);
        });

        this.dropzone.on("uploadprogress", function (file, progress, bytesSent) {
            vm.$emit("vdropzone-upload-progress", file, progress, bytesSent);
        });

        this.dropzone.on("totaluploadprogress", function (
            totaluploadprogress,
            totalBytes,
            totalBytesSent
        ) {
            vm.$emit(
                "vdropzone-total-upload-progress",
                totaluploadprogress,
                totalBytes,
                totalBytesSent
            );
        });

        this.dropzone.on("reset", function () {
            vm.$emit("vdropzone-reset");
        });

        this.dropzone.on("queuecomplete", function () {
            vm.$emit("vdropzone-queue-complete");
        });

        this.dropzone.on("drop", function (event) {
            vm.$emit("vdropzone-drop", event);
        });

        this.dropzone.on("dragstart", function (event) {
            vm.$emit("vdropzone-drag-start", event);
        });

        this.dropzone.on("dragend", function (event) {
            vm.$emit("vdropzone-drag-end", event);
        });

        this.dropzone.on("dragenter", function (event) {
            vm.$emit("vdropzone-drag-enter", event);
        });

        this.dropzone.on("dragover", function (event) {
            vm.$emit("vdropzone-drag-over", event);
        });

        this.dropzone.on("dragleave", function (event) {
            vm.$emit("vdropzone-drag-leave", event);
        });

        vm.$emit("vdropzone-mounted");
    },
    beforeDestroy() {
        if (this.destroyDropzone) this.dropzone.destroy();
    },
    methods: {
        manuallyAddFile: function (file, fileUrl) {
            file.manuallyAdded = true;
            this.dropzone.emit("addedfile", file);
            let containsImageFileType = false;
            if (
                fileUrl.indexOf(".svg") > -1 ||
                fileUrl.indexOf(".png") > -1 ||
                fileUrl.indexOf(".jpg") > -1 ||
                fileUrl.indexOf(".jpeg") > -1 ||
                fileUrl.indexOf(".gif") > -1 ||
                fileUrl.indexOf(".webp") > -1
            )
                containsImageFileType = true;
            if (
                this.dropzone.options.createImageThumbnails &&
                containsImageFileType &&
                file.size <= this.dropzone.options.maxThumbnailFilesize * 1024 * 1024
            ) {
                fileUrl && this.dropzone.emit("thumbnail", file, fileUrl);

                var thumbnails = file.previewElement.querySelectorAll(
                    "[data-dz-thumbnail]"
                );
                for (var i = 0; i < thumbnails.length; i++) {
                    thumbnails[i].style.width =
                        this.dropzoneSettings.thumbnailWidth + "px";
                    thumbnails[i].style.height =
                        this.dropzoneSettings.thumbnailHeight + "px";
                    thumbnails[i].style["object-fit"] = "contain";
                }
            }
            this.dropzone.emit("complete", file);
            if (this.dropzone.options.maxFiles) this.dropzone.options.maxFiles--;
            this.dropzone.files.push(file);
            this.$emit("vdropzone-file-added-manually", file);
        },
        setOption: function (option, value) {
            this.dropzone.options[option] = value;
        },
        removeAllFiles: function (bool) {
            this.dropzone.removeAllFiles(bool);
        },
        processQueue: function () {
            let dropzoneEle = this.dropzone;
            this.dropzone.processQueue();
            this.dropzone.on("success", function () {
                dropzoneEle.options.autoProcessQueue = true;
            });
            this.dropzone.on("queuecomplete", function () {
                dropzoneEle.options.autoProcessQueue = false;
            });
        },
        init: function () {
            return this.dropzone.init();
        },
        destroy: function () {
            return this.dropzone.destroy();
        },
        updateTotalUploadProgress: function () {
            return this.dropzone.updateTotalUploadProgress();
        },
        getFallbackForm: function () {
            return this.dropzone.getFallbackForm();
        },
        getExistingFallback: function () {
            return this.dropzone.getExistingFallback();
        },
        setupEventListeners: function () {
            return this.dropzone.setupEventListeners();
        },
        removeEventListeners: function () {
            return this.dropzone.removeEventListeners();
        },
        disable: function () {
            return this.dropzone.disable();
        },
        enable: function () {
            return this.dropzone.enable();
        },
        filesize: function (size) {
            return this.dropzone.filesize(size);
        },
        accept: function (file, done) {
            return this.dropzone.accept(file, done);
        },
        addFile: function (file) {
            return this.dropzone.addFile(file);
        },
        removeFile: function (file) {
            this.dropzone.removeFile(file);
        },
        getAcceptedFiles: function () {
            return this.dropzone.getAcceptedFiles();
        },
        getRejectedFiles: function () {
            return this.dropzone.getRejectedFiles();
        },
        getFilesWithStatus: function () {
            return this.dropzone.getFilesWithStatus();
        },
        getQueuedFiles: function () {
            return this.dropzone.getQueuedFiles();
        },
        getUploadingFiles: function () {
            return this.dropzone.getUploadingFiles();
        },
        getAddedFiles: function () {
            return this.dropzone.getAddedFiles();
        },
        getActiveFiles: function () {
            return this.dropzone.getActiveFiles();
        }
    }

});