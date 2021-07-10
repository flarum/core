import HeaderPrimary from './components/HeaderPrimary';
import HeaderSecondary from './components/HeaderSecondary';
import routes from './routes';
import Application from '../common/Application';
import Navigation from '../common/components/Navigation';
import AdminNav from './components/AdminNav';
import ExtensionData from './utils/ExtensionData';

export default class AdminApplication extends Application {
  extensionData = new ExtensionData();

  extensionCategories = {
    feature: 30,
    theme: 20,
    language: 10,
  };

  history = {
    canGoBack: () => true,
    getPrevious: () => {},
    backUrl: () => this.forum.attribute('baseUrl'),
    back: function () {
      window.location = this.backUrl();
    },
  };

  constructor() {
    super();

    routes(this);
  }

  /**
   * @inheritdoc
   */
  mount() {
    // Mithril does not render the home route on https://example.com/admin, so
    // we need to go to https://example.com/admin#/ explicitly.
    if (!document.location.hash) document.location.hash = '#/';

    m.route.prefix = '#';
    super.mount();

    m.mount(document.getElementById('app-navigation'), {
      view: () =>
        Navigation.component({
          className: 'App-backControl',
          drawer: true,
        }),
    });
    m.mount(document.getElementById('header-navigation'), Navigation);
    m.mount(document.getElementById('header-primary'), HeaderPrimary);
    m.mount(document.getElementById('header-secondary'), HeaderSecondary);
    m.mount(document.getElementById('admin-navigation'), AdminNav);
  }

  getRequiredPermissions(permission) {
    const required = [];

    if (permission === 'startDiscussion' || permission.indexOf('discussion.') === 0) {
      required.push('viewForum');
    }
    if (permission === 'discussion.delete') {
      required.push('discussion.hide');
    }
    if (permission === 'discussion.deletePosts') {
      required.push('discussion.hidePosts');
    }
    if (permission === 'user.editGroups') {
      required.push('viewHiddenGroups');
    }

    return required;
  }
}
