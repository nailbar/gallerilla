<template>
  <div>
    <button
      v-if="loggedIn"
      type="button"
      class="btn btn-danger float-end"
      v-on:click="$emit('setToken', '')"
    >Logout</button>
    <div v-else class="input-group">
      <input
        class="form-control"
        type="password"
        v-model="propToken"
        v-on:keyup.enter="$emit('login')"
        placeholder="Enter a secret to continue"
      >
      <button
        type="button"
        class="btn btn-primary"
        v-on:click="$emit('login')"
      >Login</button>
    </div>
  </div>
</template>

<script>
export default {
  props: [ 'token', 'loggedIn' ],
  computed: {
    propToken: {
      get() {
        return this.token;
      },
      set(value) {
        this.$emit('setToken', value);
      }
    }
  }
};
</script>