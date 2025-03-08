import { createRouter, createWebHashHistory } from 'vue-router';

// Import views
// We use lazy loading to improve initial load time
const Dashboard = () => import('../views/Dashboard.vue');
const Organizations = () => import('../views/Organizations.vue');
const Resources = () => import('../views/Resources.vue');
const Schedules = () => import('../views/Schedules.vue');
const Settings = () => import('../views/Settings.vue');
const UserManagement = () => import('../views/UserManagement.vue');

// Define routes
const routes = [
  {
    path: '/',
    name: 'dashboard',
    component: Dashboard,
    meta: {
      title: 'Dashboard',
      requiresAuth: true
    }
  },
  {
    path: '/organizations',
    name: 'organizations',
    component: Organizations,
    meta: {
      title: 'Organisationer',
      requiresAuth: true
    }
  },
  {
    path: '/resources',
    name: 'resources',
    component: Resources,
    meta: {
      title: 'Resurser',
      requiresAuth: true
    }
  },
  {
    path: '/schedules',
    name: 'schedules',
    component: Schedules,
    meta: {
      title: 'Scheman',
      requiresAuth: true
    }
  },
  {
    path: '/settings',
    name: 'settings',
    component: Settings,
    meta: {
      title: 'Inställningar',
      requiresAuth: true
    }
  },
  {
    path: '/users',
    name: 'users',
    component: UserManagement,
    meta: {
      title: 'Användarhantering',
      requiresAuth: true,
      requiredPermissions: ['list_users', 'edit_users']
    }
  },
  // Catch-all route for 404
  {
    path: '/:pathMatch(.*)*',
    redirect: { name: 'dashboard' }
  }
];

// Create router instance
const router = createRouter({
  history: createWebHashHistory(),
  routes
});

// Navigation guard to check authentication
router.beforeEach((to, from, next) => {
  // Check if the route requires authentication
  if (to.matched.some(record => record.meta.requiresAuth)) {
    // Check if user is logged in
    const wpData = window.wpScheduleData || {};
    const isLoggedIn = wpData.current_user && wpData.current_user.id;
    
    if (!isLoggedIn) {
      // Redirect to WordPress login
      window.location.href = wpData.admin_url ? 
        `${wpData.admin_url.replace('admin.php', 'wp-login.php')}` : 
        '/wp-login.php';
      return;
    }
  }
  
  // Continue to the route
  next();
});

export default router;
