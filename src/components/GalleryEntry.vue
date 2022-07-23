<template>
  <div class="card">
    <div class="card-body">
      <a v-bind:href="src">
        <img v-bind:src="src + '/400'" v-bind:title="title" />
      </a>
    </div>
    <div class="card-footer">
      {{ title }}
    </div>
  </div>
</template>

<script>
export default {
  props: [ 'title', 'src' ]
};
</script>

<style scoped>
img {
  width: 100%;
}
</style>