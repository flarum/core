import compat from '../common/compat';

import saveSettings from './utils/saveSettings';
import SettingDropdown from './components/SettingDropdown';
import EditCustomFooterModal from './components/EditCustomFooterModal';
import SessionDropdown from './components/SessionDropdown';
import HeaderPrimary from './components/HeaderPrimary';
import AppearancePage from './components/AppearancePage';
import Page from './components/Page';
import StatusWidget from './components/StatusWidget';
import HeaderSecondary from './components/HeaderSecondary';
import SettingsModal from './components/SettingsModal';
import DashboardWidget from './components/DashboardWidget';
import AddExtensionModal from './components/AddExtensionModal';
import ExtensionsPage from './components/ExtensionsPage';
import AdminLinkButton from './components/AdminLinkButton';
import PermissionGrid from './components/PermissionGrid';
import MailPage from './components/MailPage';
import AuthPage from './components/AuthPage';
import UploadImageButton from './components/UploadImageButton';
import LoadingModal from './components/LoadingModal';
import DashboardPage from './components/DashboardPage';
import BasicsPage from './components/BasicsPage';
import EditCustomHeaderModal from './components/EditCustomHeaderModal';
import PermissionsPage from './components/PermissionsPage';
import PermissionDropdown from './components/PermissionDropdown';
import AdminNav from './components/AdminNav';
import EditCustomCssModal from './components/EditCustomCssModal';
import EditGroupModal from './components/EditGroupModal';
import routes from './routes';
import AdminApplication from './AdminApplication';

export default Object.assign(compat, {
  'utils/saveSettings': saveSettings,
  'components/SettingDropdown': SettingDropdown,
  'components/EditCustomFooterModal': EditCustomFooterModal,
  'components/SessionDropdown': SessionDropdown,
  'components/HeaderPrimary': HeaderPrimary,
  'components/AppearancePage': AppearancePage,
  'components/Page': Page,
  'components/StatusWidget': StatusWidget,
  'components/HeaderSecondary': HeaderSecondary,
  'components/SettingsModal': SettingsModal,
  'components/DashboardWidget': DashboardWidget,
  'components/AddExtensionModal': AddExtensionModal,
  'components/ExtensionsPage': ExtensionsPage,
  'components/AdminLinkButton': AdminLinkButton,
  'components/PermissionGrid': PermissionGrid,
  'components/MailPage': MailPage,
  'components/AuthPage': AuthPage,
  'components/UploadImageButton': UploadImageButton,
  'components/LoadingModal': LoadingModal,
  'components/DashboardPage': DashboardPage,
  'components/BasicsPage': BasicsPage,
  'components/EditCustomHeaderModal': EditCustomHeaderModal,
  'components/PermissionsPage': PermissionsPage,
  'components/PermissionDropdown': PermissionDropdown,
  'components/AdminNav': AdminNav,
  'components/EditCustomCssModal': EditCustomCssModal,
  'components/EditGroupModal': EditGroupModal,
  routes: routes,
  AdminApplication: AdminApplication,
});
