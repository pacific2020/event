import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router';
import { useAuthStore } from '@/store/auth';

// Layouts
import PublicLayout from '@/layouts/PublicLayout.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import GraduandLayout from '@/layouts/GraduandLayout.vue';
import RecordLayout from '@/layouts/RecordLayout.vue';
import ScannerLayout from '@/layouts/ScannerLayout.vue';
import ProtocalLayout from '@/layouts/ProtocalLayout.vue';

// Views (Consider lazy loading these for better performance)
const HomeView = () => import('../views/HomeView.vue');
const LoginView = () => import('@/views/Admin/LoginView.vue');

const routes: RouteRecordRaw[] = [
  // PUBLIC ROUTES
  {
    path: '/',
    component: PublicLayout,
    children: [
      { path: '', name: 'home', component: HomeView },
      { path: 'apply', name: 'application', component: () => import('@/views/application.vue') },
    ]
  },

  // ADMIN ROUTES
  {
    path: '/Admin',
    component: AdminLayout,
    meta: { requiresAuth: true, role: 'Admin' },
    children: [
      { path: 'login', name: 'admin.login', component: LoginView, meta: { requiresAuth: false } },
      { path: 'dashboard', name: 'admin.dashboard', component: () => import('@/views/Admin/DashboardView.vue') },
      { path: 'user/create', name: 'admin.create.user', component: () => import('@/views/Admin/CreateUser.vue') },
      { path: 'user/view', name: 'admin.view.user', component: () => import('@/views/Admin/ViewUser.vue') },
      { path: 'event/create', name: 'admin.create.event', component: () => import('@/views/Admin/CreateEvent.vue') },
      { path: 'invitation/guest', name: 'admin.invitation.guest', component: () => import('@/views/Admin/GuestInvitationView.vue') },
      { path: 'graduation/list', name: 'admin.graduation.list', component: () => import('@/views/Admin/GraduationList.vue') },
      { path: 'invitation/view', name: 'admin.invitation.view', component: () => import('@/views/Scanner/ViewInvitations.vue') },
    ]
  },

  // GRADUAND ROUTES
  {
    path: '/Graduand',
    component: GraduandLayout,
    meta: { requiresAuth: true, role: 'Graduand' },
    children: [
      { path: 'dashboard', name: 'graduand.dashboard', component: () => import('@/views/Graduand/DashboardGraduandview.vue') },
      { path: 'generate/create', name: 'graduand.generate.create', component: () => import('@/views/Graduand/Generate.vue') }
    ]
  },

  // RECORD OFFICER
  {
    path: '/RecordOfficer',
    component: RecordLayout,
    meta: { requiresAuth: true, role: 'RecordOfficer' },
    children: [
      { path: 'dashboard', name: 'recordOfficer.dashboard', component: () => import('@/views/RecordOfficer/DashboardRecordOfficer.vue') },
      { path: 'gown/create', name: 'recordOfficer.gown.create', component: () => import('@/views/RecordOfficer/GiveGown.vue') },
      { path: 'gown/view', name: 'recordOfficer.gown.view', component: () => import('@/views/RecordOfficer/ViewGown.vue') },
    ]
  },

  // SCANNER & PROTOCOL (Simplified)
  {
    path: '/Scanner',
    component: ScannerLayout,
    meta: { requiresAuth: true, role: 'Scanner' },
    children: [
      { path: 'dashboard', name: 'scanner.dashboard', component: () => import('@/views/Scanner/DashboardScanner.vue') },
      { path: 'invitation/view', name: 'scanner.invitation.view', component: () => import('@/views/Scanner/ViewInvitations.vue') },
      { path: 'open', name: 'scanner.open', component: () => import('@/views/Scanner/ScanningInvitation.vue') },
    ]
  },

  {
    path: '/Protocol',
    component: ProtocalLayout,
    meta: { requiresAuth: true, role: 'Protocol' },
    children: [
      { path: 'dashboard', name: 'protocol.dashboard', component: () => import('@/views/Protocol/DashboardProtocal.vue') },
      { path: 'invitation/view', name: 'protocol.invitation.view', component: () => import('@/views/Scanner/ViewInvitations.vue') },
      { path: 'open', name: 'protocol.open', component: () => import('@/views/Scanner/ScanningInvitation.vue') },
    ]
  },

  // 404 CATCH-ALL
  {
    path: '/:pathMatch(.*)*',
    redirect: '/'
  }
];

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
  scrollBehavior: () => ({ top: 0 }) // Good for UX
});

/**
 * GLOBAL NAVIGATION GUARD
 */
router.beforeEach(async (to, from, next) => {
  const auth = useAuthStore();
  const token = localStorage.getItem('token');
  const userRole = localStorage.getItem('user_role');

  // 1. Handle Guest Access to Login Page
  if (to.name === 'admin.login' && token) {
    return next(`/${userRole}/dashboard`);
  }

  // 2. Check Authentication
  if (to.meta.requiresAuth && !token) {
    return next({ name: 'admin.login' });
  }

  // 3. Hydrate User state on refresh
  if (token && !auth.user) {
    try {
      await auth.getUser();
    } catch (error) {
      auth.cleanState();
      return next({ name: 'admin.login' });
    }
  }

  // 4. Role-Based Authorization
  // This checks if the user's role matches the 'role' meta tag on the route
  // if (to.meta.role && to.meta.role !== auth.user?.role) {
  //   return next({ name: 'home' }); // or a 403 Forbidden page
  // }

  next();
});

export default router;