import Component from '../../common/Component';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import Button from '../../common/components/Button';
import humanTime from '../../common/helpers/humanTime';

export default class TokensManager extends Component {
  oninit(vnode) {
    super.oninit(vnode);

    this.refresh();
  }

  refresh() {
    this.tokens = null;

    app.request({
      method: 'GET',
      url: app.forum.attribute('apiUrl') + '/tokens',
    }).then(payload => {
      this.tokens = app.store.pushPayload(payload);
      m.redraw();
    });

    // TODO: pagination
  }

  view() {
    if (this.tokens === null) {
      return LoadingIndicator.component();
    }

    console.log(this.tokens);

    return m('table', [
      m('thead', m('tr', [
        m('th', 'Token'),
        m('th', 'Created'),
        m('th', 'Last usage'),
        m('th', 'Lifetime'),
        m('th', 'IP'),
        m('th', 'User Agent'),
        m('th', 'Actions'),
      ])),
      m('tbody', this.tokens.map(token => m('tr', [
        m('td', token.token()),
        m('td', humanTime(token.createdAt())),
        m('td', token.current() ? m('strong', 'Current session') : humanTime(token.lastActivityAt())),
        m('td', token.lifetimeSeconds()),
        m('td', token.lastIpAddress()),
        m('td', token.lastUserAgent()),
        m('td', Button.component({
          className: 'Button',
          onclick: () => {
            token.delete().then(() => {
              this.refresh();
            });
          },
          disabled: token.current(),
        }, 'Revoke')),
      ]))),
    ]);
  }
}
