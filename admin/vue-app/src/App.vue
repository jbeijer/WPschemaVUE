<template>
  <div class="wpschema-vue-admin">
    <header class="wpschema-vue-admin-header">
      <h1>{{ pageTitle }}</h1>
    </header>
    
    <div class="wpschema-vue-admin-content">
      <aside class="wpschema-vue-admin-sidebar" v-if="showSidebar">
        <nav>
          <ul>
            <li>
              <router-link :to="{ name: 'dashboard' }">Dashboard</router-link>
            </li>
            <li>
              <router-link :to="{ name: 'organizations' }">Organisationer</router-link>
            </li>
            <li>
              <router-link :to="{ name: 'resources' }">Resurser</router-link>
            </li>
            <li>
              <router-link :to="{ name: 'schedules' }">Scheman</router-link>
            </li>
            <li>
              <router-link :to="{ name: 'settings' }">Inställningar</router-link>
            </li>
          </ul>
        </nav>
      </aside>
      
      <main class="wpschema-vue-admin-main">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script>
export default {
  name: 'App',
  data() {
    return {
      showSidebar: true
    };
  },
  computed: {
    pageTitle() {
      // Get the current route name and return a corresponding title
      const routeName = this.$route.name;
      
      switch (routeName) {
        case 'dashboard':
          return 'Dashboard';
        case 'organizations':
          return 'Organisationer';
        case 'resources':
          return 'Resurser';
        case 'schedules':
          return 'Scheman';
        case 'settings':
          return 'Inställningar';
        default:
          return 'Schema Manager';
      }
    }
  },
  mounted() {
    // Check if we're on a specific page based on the data-page attribute
    const appElement = document.getElementById('wpschema-vue-admin-app');
    if (appElement) {
      const page = appElement.getAttribute('data-page');
      if (page) {
        // Navigate to the corresponding route
        this.$router.push({ name: page });
      }
    }
  }
};
</script>

<style>
/* Basic styles for the admin app */
.wpschema-vue-admin {
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
  color: #333;
  margin: 0;
  padding: 0;
}

.wpschema-vue-admin-header {
  margin-bottom: 20px;
}

.wpschema-vue-admin-content {
  display: flex;
  min-height: 500px;
}

.wpschema-vue-admin-sidebar {
  width: 200px;
  margin-right: 20px;
  background-color: #f9f9f9;
  border-right: 1px solid #e5e5e5;
}

.wpschema-vue-admin-sidebar nav ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.wpschema-vue-admin-sidebar nav ul li {
  margin: 0;
  padding: 0;
}

.wpschema-vue-admin-sidebar nav ul li a {
  display: block;
  padding: 10px 15px;
  color: #0073aa;
  text-decoration: none;
  border-bottom: 1px solid #e5e5e5;
}

.wpschema-vue-admin-sidebar nav ul li a:hover,
.wpschema-vue-admin-sidebar nav ul li a.router-link-active {
  background-color: #0073aa;
  color: #fff;
}

.wpschema-vue-admin-main {
  flex: 1;
  padding: 0 20px;
}
</style>
