import Component from '../../common/Component';
import Link from '../../common/components/Link';
import avatar from '../../common/helpers/avatar';
import username from '../../common/helpers/username';
import highlight from '../../common/helpers/highlight';

/**
 * The `PostPreview` component shows a link to a post containing the avatar and
 * username of the author, and a short excerpt of the post's content.
 *
 * ### Attrs
 *
 * - `post`
 */
export default class PostPreview extends Component {
  view() {
    const post = this.attrs.post;
    const user = post.user();
    const content = post.contentType() === 'comment' && post.contentPlain();
    const excerpt = content ? highlight(content, this.attrs.highlight, 300) : '';

    return (
      <Link className="PostPreview" href={app.route.post(post)} onclick={this.attrs.onclick}>
        <span className="PostPreview-content">
          {avatar(user)}
          {username(user)} <span className="PostPreview-excerpt">{excerpt}</span>
        </span>
      </Link>
    );
  }
}
