<script setup>
import { Head, router, usePage } from "@inertiajs/vue3";
import { ref, onMounted } from "vue";
import { createUpload } from "@mux/upchunk";

import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import ListVideosUpload from "@/Components/VideoFlix/ListVideosUpload.vue";

import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/VideoFlix/TextInput.vue";
import NavLink from "@/Components/NavLink.vue";

onMounted(() => {
    Echo.channel("videos")
        .listen(".App\\Events\\VideoThumbCreated", (e) => {
            const video = getVideoById(e.videoId);
            console.log(e, video);
            if (!video) return;

            video.thumb = e.thumb;
        })
        .listen(".App\\Events\\VideoEncodingStarted", (e) => {
            const video = getVideoById(e.videoId);

            if (!video) return;

            video.encoding = true;
        })
        .listen(".App\\Events\\VideoEncodingProgress", (e) => {
            const video = getVideoById(e.videoId);

            if (!video) return;

            video.encodingProgress = e.percentage;
        })
        .listen(".App\\Events\\VideoEncodingFinished", (e) => {
            const video = getVideoById(e.videoId);

            if (!video) return;

            video.encoding = false;
        });
});

defineProps({
    content: {},
});

const isDragged = ref(false);
const uploads = ref([]);

const getVideoById = (videoId) => {
    return uploads.value.find((video) => video.id == videoId);
};

const cancelUpload = (videoId) => {
    getVideoById(videoId).file.abort();

    router.delete(route("media.contents.videos.destroy", videoId), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            uploads.value = uploads.value.filter(
                (video) => video.id !== videoId
            );
        },
    });
};

const pauseUpload = (videoId) => {
    getVideoById(videoId).file.pause();
};

const resumeUpload = (videoId) => {
    getVideoById(videoId).file.resume();
};

const initChunkUpload = (file, params) => {
    const upload = createUpload({
        endpoint: route("media.contents.videos.upload.process", params),
        headers: {
            "X-CSRF-TOKEN": usePage().props.csrf_token,
        },
        method: "post",
        file: file,
        chunkSize: 20 * 1024,
    });

    upload.on("progress", (e) => {
        getVideoById(params.video).uploadProgress = e.detail;
    });

    upload.on("success", (e) => {
        getVideoById(params.video).uploading = false;
    });

    return upload;
};

const mainHandleVideos = (videos) => {
    const content = usePage().props.content;

    Array.from(videos).forEach((video) => {
        axios
            .post(route("media.contents.videos.store", content), {
                name: video.name,
            })
            .then((response) => {
                uploads.value.unshift({
                    id: response.data.id,
                    name: video.name,
                    file: initChunkUpload(video, {
                        content: content,
                        video: response.data.id,
                    }),
                    uploading: true,
                    uploadProgress: 0,
                    encoding: false,
                    encodingProgress: 0,
                    thumb: null,
                });
            });
    });
};
</script>
<template>
    <Head title="Conteúdos" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight"
            >
                Atualizar Conteúdo
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div
                    class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800"
                >
                    <div class="shadow overflow-hidden sm:rounded-lg">
                        <div class="p-6 text-gray-900 dark:text-gray-100">
                            <div
                                class="w-full flex items-center justify-center"
                            >
                                <InputLabel
                                    @dragover.prevent="isDragged = true"
                                    @dragleave.prevent="isDragged = false"
                                    @drop.prevent="
                                        mainHandleVideos(
                                            $event.dataTransfer.files
                                        )
                                    "
                                    for="videos"
                                    value="Arraste seus vídeos para realizar o upload..."
                                    class="w-full h-28 border-2 border-dashed border-gray-500 bg-gray-700 rounded flex items-center justify-center"
                                    :class="{
                                        'bg-gray-900': isDragged,
                                    }"
                                />

                                <TextInput
                                    id="videos"
                                    type="file"
                                    class="sr-only"
                                    @change="
                                        mainHandleVideos($event.target.files)
                                    "
                                />
                            </div>

                            <ListVideosUpload
                                v-for="upload of uploads"
                                :key="upload.id"
                                :upload="upload"
                                :content="content.id"
                                @cancel="cancelUpload"
                                @resume="resumeUpload"
                                @pause="pauseUpload"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
