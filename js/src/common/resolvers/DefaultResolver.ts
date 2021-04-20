import Mithril from 'mithril';
import ErrorPage from '../components/ErrorPage';

/**
 * Generates a route resolver for a given component.
 * In addition to regular route resolver functionality:
 * - It provide the current route name as an attr
 * - It sets a key on the component so a rerender will be triggered on route change.
 */
export default class DefaultResolver {
  component: Mithril.Component;
  routeName: string;

  constructor(component, routeName) {
    this.component = component;
    this.routeName = routeName;
  }

  /**
   * When a route change results in a changed key, a full page
   * rerender occurs. This method can be overriden in subclasses
   * to prevent rerenders on some route changes.
   */
  makeKey() {
    return this.routeName + JSON.stringify(m.route.param());
  }

  makeAttrs(vnode) {
    return {
      ...vnode.attrs,
      routeName: this.routeName,
    };
  }

  onmatch(args, requestedPath, route) {
    const errorCode = app.data.errorCode;
    if (errorCode) {
      delete app.data.errorCode;
      return { view: () => ErrorPage.component({ errorCode }) };
    }

    return this.component;
  }

  render(vnode) {
    return [{ ...vnode, attrs: this.makeAttrs(vnode), key: this.makeKey() }];
  }
}
