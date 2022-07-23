<template>
  <div class="container">
    <div class="card mt-3 mb-3">
      <div class="card-header">
        <div class="container-fluid p-0">
          <div class="row">
            <div class="col-8">
              <h5>{{ me }}</h5>
            </div>
            <div class="col-4">
              <LoginComp v-bind:token="token" v-bind:loggedIn="loggedIn" v-on:setToken="setToken" v-on:login="login" />
            </div>
          </div>
        </div>
      </div>
      <div class="card-body">
        <gallery-comp v-bind:entries="galleryData" v-bind:photoPrefix="config.backendUrl + '?a=gallery/'" />
      </div>
    </div>
  </div>
</template>

<script>
import GalleryComp from './components/GalleryComp.vue';
import LoginComp from './components/LoginComp.vue';
import axios from 'axios';

export default {
  name: 'App',
  components: {
    GalleryComp,
    LoginComp
  },
  data() {
    return {
      config: {},
      galleryData: [],
      token: '',
      me: 'Please login',
      loggedIn: false
    };
  },
  methods: {
    setToken(value) {
      const oldToken = this.token;
      this.token = value;
      if(oldToken != value) {
        this.me = 'Please login';
        this.galleryData = [];
        this.loggedIn = false;
        localStorage.setItem('gallerillaToken', '');
      }
    },
    login() {
      axios.get(this.config.backendUrl + '?a=me', {
        headers: {
          Authorization: 'Bearer ' + this.token
        }
      }).then(resp => {
        if(resp.data.status === 'OK') {
          localStorage.setItem('gallerillaToken', this.token);
          this.loggedIn = true;
          this.me = resp.data.data;
          axios.get(this.config.backendUrl + '?a=gallery', {
            headers: {
              Authorization: 'Bearer ' + this.token
            }
          }).then(resp => {
            this.galleryData = resp.data.data;
          });
        } else {
          this.galleryData = [];
          this.me = 'Not a valid secret';
        }
      });
    }
  },
  mounted() {
    axios.get('config.json').then(resp => {
      this.config = resp.data;
      const storedToken = localStorage.getItem('gallerillaToken');
      if(storedToken) {
        this.token = storedToken;
        this.login();
      }
    });
  }
}
</script>
