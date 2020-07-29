import UserPage from './UserPage';
import DiscussionList from './DiscussionList';
import DiscussionListState from '../states/DiscussionListState';

/**
 * The `DiscussionsUserPage` component shows a discussion list inside of a user
 * page.
 */
export default class DiscussionsUserPage extends UserPage {
  init() {
    super.init();

    this.loadUser(m.route.param('username'));
  }

  show(user) {
    super.show(user);

    this.state = new DiscussionListState({
      q: 'author:' + user.username(),
      sort: 'newest',
    });

    this.state.refresh();

    app.current.set('discussions', this.state);
  }

  content() {
    return <div className="DiscussionsUserPage">{DiscussionList.component({ state: this.state })}</div>;
  }
}
