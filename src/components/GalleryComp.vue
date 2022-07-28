<template>
  <div class="container-fluid">
    <div class="row">
      <div v-for="entry of entries" class="col-md-4 mb-3" v-bind:key="entry.id">
        <gallery-entry v-bind:title="entry.name" v-bind:src="photoPrefix + entry.id" />
      </div>
    </div>
  </div>
</template>

<script>
import GalleryEntry from './GalleryEntry.vue';
export default {
  components: { GalleryEntry },
  props: [ 'entries', 'photoPrefix' ],
  data() {
    return {};
  }
};
</script>