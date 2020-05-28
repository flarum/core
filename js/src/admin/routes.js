import DashboardPage from './components/DashboardPage';
import BasicsPage from './components/BasicsPage';
import PermissionsPage from './components/PermissionsPage';
import AppearancePage from './components/AppearancePage';
import ExtensionsPage from './components/ExtensionsPage';
import MailPage from './components/MailPage';
import AuthPage from './components/AuthPage';

/**
 * The `routes` initializer defines the forum app's routes.
 *
 * @param {App} app
 */
export default function (app) {
  app.routes = {
    dashboard: { path: '/', component: DashboardPage.component() },
    basics: { path: '/basics', component: BasicsPage.component() },
    permissions: { path: '/permissions', component: PermissionsPage.component() },
    appearance: { path: '/appearance', component: AppearancePage.component() },
    extensions: { path: '/extensions', component: ExtensionsPage.component() },
    mail: { path: '/mail', component: MailPage.component() },
    auth: { path: '/auth', component: AuthPage.component() },
  };
}
