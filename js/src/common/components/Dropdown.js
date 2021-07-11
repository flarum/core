import Component from '../Component';
import icon from '../helpers/icon';
import listItems from '../helpers/listItems';

/**
 * The `Dropdown` component displays a button which, when clicked, shows a
 * dropdown menu beneath it.
 *
 * ### Attrs
 *
 * - `buttonClassName` A class name to apply to the dropdown toggle button.
 * - `menuClassName` A class name to apply to the dropdown menu.
 * - `icon` The name of an icon to show in the dropdown toggle button.
 * - `caretIcon` The name of an icon to show on the right of the button.
 * - `label` The label of the dropdown toggle button. Defaults to 'Controls'.
 * - `accessibleToggleLabel` The label used to describe the dropdown toggle button to assistive readers. Defaults to 'Toggle dropdown menu'.
 * - `onhide`
 * - `onshow`
 *
 * The children will be displayed as a list inside of the dropdown menu.
 */
export default class Dropdown extends Component {
  static initAttrs(attrs) {
    attrs.className = attrs.className || '';
    attrs.buttonClassName = attrs.buttonClassName || '';
    attrs.menuClassName = attrs.menuClassName || '';
    attrs.label = attrs.label || '';
    attrs.caretIcon = typeof attrs.caretIcon !== 'undefined' ? attrs.caretIcon : 'fas fa-caret-down';
    attrs.accessibleToggleLabel = attrs.accessibleToggleLabel || app.translator.trans('core.lib.dropdown.toggle_dropdown_accessible_label');
  }

  oninit(vnode) {
    super.oninit(vnode);

    this.showing = false;
  }

  view(vnode) {
    const items = vnode.children ? listItems(vnode.children) : [];
    const renderItems = this.attrs.lazyDraw ? this.showing : true;

    return (
      <div className={'ButtonGroup Dropdown dropdown ' + this.attrs.className + ' itemCount' + items.length + (this.showing ? ' open' : '')}>
        {this.getButton(vnode.children)}
        {renderItems && this.getMenu(items)}
      </div>
    );
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    // When opening the dropdown menu, work out if the menu goes beyond the
    // bottom of the viewport. If it does, we will apply class to make it show
    // above the toggle button instead of below it.
    this.$().on('shown.bs.dropdown', () => {
      const { lazyDraw, onshow } = this.attrs;

      this.showing = true;

      // If using lazy drawing, redraw before calling `onshow` function
      // to make sure the menu DOM exists in case the callback tries to use it.
      if (lazyDraw) {
        m.redraw.sync();
      }

      if (onshow) {
        onshow();
      }

      // If not using lazy drawing, keep previous functionality
      // of redrawing after calling onshow()
      if (!lazyDraw) {
        m.redraw();
      }

      const $menu = this.$('.Dropdown-menu');
      const isRight = $menu.hasClass('Dropdown-menu--right');

      $menu.removeClass('Dropdown-menu--top Dropdown-menu--right');

      $menu.toggleClass('Dropdown-menu--top', $menu.offset().top + $menu.height() > $(window).scrollTop() + $(window).height());

      if ($menu.offset().top < 0) {
        $menu.removeClass('Dropdown-menu--top');
      }

      $menu.toggleClass('Dropdown-menu--right', isRight || $menu.offset().left + $menu.width() > $(window).scrollLeft() + $(window).width());
    });

    this.$().on('hidden.bs.dropdown', () => {
      this.showing = false;

      if (this.attrs.onhide) {
        this.attrs.onhide();
      }

      m.redraw();
    });
  }

  /**
   * Get the template for the button.
   *
   * @return {*}
   * @protected
   */
  getButton(children) {
    return (
      <button
        className={'Dropdown-toggle ' + this.attrs.buttonClassName}
        aria-haspopup="menu"
        aria-label={this.attrs.accessibleToggleLabel}
        data-toggle="dropdown"
        onclick={this.attrs.onclick}
      >
        {this.getButtonContent(children)}
      </button>
    );
  }

  /**
   * Get the template for the button's content.
   *
   * @return {*}
   * @protected
   */
  getButtonContent(children) {
    return [
      this.attrs.icon ? icon(this.attrs.icon, { className: 'Button-icon' }) : '',
      <span className="Button-label">{this.attrs.label}</span>,
      this.attrs.caretIcon ? icon(this.attrs.caretIcon, { className: 'Button-caret' }) : '',
    ];
  }

  getMenu(items) {
    return <ul className={'Dropdown-menu dropdown-menu ' + this.attrs.menuClassName}>{items}</ul>;
  }
}
