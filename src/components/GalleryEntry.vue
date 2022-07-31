<template>
  <div class="card">
    <div class="card-body">
      <a v-bind:href="src">
        <img v-bind:src="src + '/400'" v-bind:title="title" />
      </a>
    </div>
    <div class="card-footer">
      <div class="float-end edit" v-on:click="$emit('edit', id)">&#128393;</div>
      {{ title }}
      <pre v-if="meta" class="border rounded p-1 mt-1">{{ meta }}</pre>
    </div>
  </div>
</template>

<script>
export default {
  props: [ 'id', 'title', 'src', 'meta' ]
};
</script>

<style scoped>
img {
  width: 100%;
}
pre {
  background-color: white;
}
.edit {
  cursor: pointer;
}
</style>