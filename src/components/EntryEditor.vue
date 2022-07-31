<template>
  <div class="modal fade show d-block" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ name }}</h5>
          <button type="button" class="btn-close" v-on:click="$emit('closeEditor')"></button>
        </div>
        <div class="modal-body">
          <div v-if="waiting" class="spinner-border"></div>
          <template v-else>
            <div class="mb-3">
              <a v-bind:href="src">
                <img v-bind:src="src + '/400'" v-bind:name="name" />
              </a>
            </div>
            <textarea class="form-control" ref="meta" v-model="meta" rows="5"></textarea>
          </template>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" v-on:click="$emit('closeEditor')">Close</button>
          <button type="button" class="btn btn-primary" v-on:click="save">Save</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  props: [ 'id', 'backendUrl', 'token' ],
  data() {
    return {
      name: 'Loading...',
      meta: '',
      src: '',
      waiting: true
    }
  },
  methods: {
    save() {
      this.name = 'Saving...';
      this.waiting = true;
      axios.post(this.backendUrl + '?a=meta/' + this.id, {
        meta: this.meta
      }, {
        headers: {
          Authorization: 'Bearer ' + this.token
        }
      }).then(resp => {
        if(resp.data.status === 'OK') {
          this.$emit('closeEditor', true, this.meta);
        }
      });
    }
  },
  mounted() {
    this.name = 'Loading...';
    this.meta = '';
    this.src = '';
    this.waiting = true;

    axios.get(this.backendUrl + '?a=meta/' + this.id, {
      headers: {
        Authorization: 'Bearer ' + this.token
      }
    }).then(resp => {
      if(resp.data.status === 'OK') {
        this.name = resp.data.data.name;
        this.meta = resp.data.data.meta;
        this.src = this.backendUrl + '?a=gallery/' + this.id;
        this.waiting = false;
        this.$nextTick(() => {
          this.$refs.meta.focus(); // Focus on the textarea after it has loaded
        });
      }
    });
  }
};
</script>

<style scoped>
img {
  width: 100%;
}
</style>