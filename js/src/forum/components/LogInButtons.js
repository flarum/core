import Component from '../../common/Component';
import ItemList from '../../common/utils/ItemList';
import LogInButton from './LogInButton';

/**
 * The `LogInButtons` component displays a collection of social login buttons.
 */
export default class LogInButtons extends Component {
  view() {
    return <div className="LogInButtons">{this.items().toArray()}</div>;
  }

  /**
   * Build a list of LogInButton components.
   *
   * @return {ItemList}
   * @public
   */
  items() {
    const items = new ItemList();

    const providers = app.forum.data.attributes.ssoProviders;

    for (const provider in providers) {
      const className = `Button LogInButton--${provider}`;
      const path = `/auth/${provider}`;
      const style = {};
      if (providers[provider].buttonColor) {
        style['background-color'] = providers[provider].buttonColor;
      }
      if (providers[provider].buttonTextColor) {
        style['color'] = providers[provider].buttonTextColor;
      }
      items.add(
        provider,
        <LogInButton className={className} icon={providers[provider].icon ? providers[provider].icon : ''} path={path} style={style}>
          {providers[provider].buttonText ? providers[provider].buttonText : provider}
        </LogInButton>
      );
    }

    return items;
  }
}
