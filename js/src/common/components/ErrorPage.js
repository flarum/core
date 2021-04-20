import Link from './Link';
import Page from './Page';

export default class ErrorPage extends Page {
  oninit(vnode) {
    super.oninit(vnode);

    this.title = app.translator.trans(`core.forum.error.${this.attrs.errorCode}_title`);
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    app.setTitle(this.title);
  }

  view() {
    const links = {
      404: '/',
    };

    const link = links[this.attrs.errorCode];

    return (
      <div className="ErrorPage">
        <div className="container">
          <h2>{this.title}</h2>
          <p>{app.translator.trans(`core.forum.error.${this.attrs.errorCode}_text`)}</p>
          <p>
            {link && (
              <Link href={link}>
                {app.translator.trans(`core.forum.error.${this.attrs.errorCode}_link_text`, { forum: app.forum.attribute('title') })}
              </Link>
            )}
          </p>
        </div>
      </div>
    );
  }
}
