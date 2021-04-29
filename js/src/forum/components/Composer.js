import Component from '../../common/Component';
import ItemList from '../../common/utils/ItemList';
import ComposerButton from './ComposerButton';
import listItems from '../../common/helpers/listItems';
import classList from '../../common/utils/classList';
import ComposerState from '../states/ComposerState';

/**
 * The `Composer` component displays the composer. It can be loaded with a
 * content component with `load` and then its position/state can be altered with
 * `show`, `hide`, `close`, `minimize`, `fullScreen`, and `exitFullScreen`.
 */
export default class Composer extends Component {
  oninit(vnode) {
    super.oninit(vnode);

    /**
     * The composer's "state".
     *
     * @type {ComposerState}
     */
    this.state = this.attrs.state;

    /**
     * Whether or not the composer currently has focus.
     *
     * @type {Boolean}
     */
    this.active = false;

    // Store the initial position so that we can trigger animations correctly.
    this.prevPosition = this.state.position;
  }

  view() {
    const body = this.state.body;
    const classes = {
      normal: this.state.position === ComposerState.Position.NORMAL,
      minimized: this.state.position === ComposerState.Position.MINIMIZED,
      fullScreen: this.state.position === ComposerState.Position.FULLSCREEN,
      active: this.active,
      visible: this.state.isVisible(),
    };

    // Set up a handler so that clicks on the content will show the composer.
    const showIfMinimized = this.state.position === ComposerState.Position.MINIMIZED ? this.state.show.bind(this.state) : undefined;

    return (
      <div className={'Composer ' + classList(classes)}>
        <div className="Composer-handle" oncreate={this.configHandle.bind(this)} />
        <ul className="Composer-controls">{listItems(this.controlItems().toArray())}</ul>
        <div className="Composer-content" onclick={showIfMinimized}>
          {body.componentClass ? body.componentClass.component({ ...body.attrs, composer: this.state, disabled: classes.minimized }) : ''}
        </div>
      </div>
    );
  }

  onupdate() {
    if (this.state.position === this.prevPosition) {
      // Set the height of the Composer element and its contents on each redraw,
      // so that they do not lose it if their DOM elements are recreated.
      this.updateHeight();
    } else {
      this.animatePositionChange();

      this.prevPosition = this.state.position;
    }
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    this.initializeHeight();
    this.$().hide().css('bottom', -this.state.computedHeight());

    // Whenever any of the inputs inside the composer are have focus, we want to
    // add a class to the composer to draw attention to it.
    this.$().on('focus blur', ':input,.TextEditor-editorContainer', (e) => {
      this.active = e.type === 'focusin';
      m.redraw();
    });

    // When the escape key is pressed on any inputs, close the composer.
    this.$().on('keydown', ':input,.TextEditor-editorContainer', 'esc', () => this.state.close());

    this.handlers = {};

    $(window)
      .on('resize', (this.handlers.onresize = this.updateHeight.bind(this)))
      .trigger('resize');

    $(document)
      .on('mousemove', (this.handlers.onmousemove = this.onmousemove.bind(this)))
      .on('mouseup', (this.handlers.onmouseup = this.onmouseup.bind(this)));
  }

  onremove() {
    $(window).off('resize', this.handlers.onresize);

    $(document).off('mousemove', this.handlers.onmousemove).off('mouseup', this.handlers.onmouseup);
  }

  /**
   * Add the necessary event handlers to the composer's handle so that it can
   * be used to resize the composer.
   */
  configHandle(vnode) {
    const composer = this;

    $(vnode.dom)
      .css('cursor', 'row-resize')
      .on('dragstart mousedown', (e) => e.preventDefault())
      .on('mousedown', function (e) {
        composer.mouseStart = e.clientY;
        composer.heightStart = composer.$().height();
        composer.handle = $(this);
        $('body').css('cursor', 'row-resize');
      });
  }

  /**
   * Resize the composer according to mouse movement.
   *
   * @param {Event} e
   */
  onmousemove(e) {
    if (!this.handle) return;

    // Work out how much the mouse has been moved, and set the height
    // relative to the old one based on that. Then update the content's
    // height so that it fills the height of the composer, and update the
    // body's padding.
    const deltaPixels = this.mouseStart - e.clientY;
    this.changeHeight(this.heightStart + deltaPixels);

    // Update the body's padding-bottom so that no content on the page will ever
    // get permanently hidden behind the composer. If the user is already
    // scrolled to the bottom of the page, then we will keep them scrolled to
    // the bottom after the padding has been updated.
    const scrollTop = $(window).scrollTop();
    const anchorToBottom = scrollTop > 0 && scrollTop + $(window).height() >= $(document).height();
    this.updateBodyPadding(anchorToBottom);
  }

  /**
   * Finish resizing the composer when the mouse is released.
   */
  onmouseup() {
    if (!this.handle) return;

    this.handle = null;
    $('body').css('cursor', '');
  }

  /**
   * Draw focus to the first focusable content element (the text editor).
   */
  focus() {
    this.$('.Composer-content :input:enabled:visible, .TextEditor-editor').first().trigger('focus');
  }

  /**
   * Update the DOM to reflect the composer's current height. This involves
   * setting the height of the composer's root element, and adjusting the height
   * of any flexible elements inside the composer's body.
   */
  updateHeight() {
    const height = this.state.computedHeight();
    const $flexible = this.$('.Composer-flexible');

    this.$().height(height);

    if ($flexible.length) {
      const headerHeight = $flexible.offset().top - this.$().offset().top;
      const footerHeight = this.$('.Composer-footer').outerHeight(true);

      const height = this.$().outerHeight() - headerHeight - footerHeight;
      $flexible.css('height', `calc(${height}px + var(--toolbar-offset))`);
    }
  }

  /**
   * Update the amount of padding-bottom on the body so that the page's
   * content will still be visible above the composer when the page is
   * scrolled right to the bottom.
   */
  updateBodyPadding() {
    const visible =
      this.state.position !== ComposerState.Position.HIDDEN && this.state.position !== ComposerState.Position.MINIMIZED && app.screen() !== 'phone';

    const paddingBottom = visible ? this.state.computedHeight() - parseInt($('#app').css('padding-bottom')) + 16 : 0;

    $('#content').css({ paddingBottom });
  }

  /**
   * Trigger the right animation depending on the desired new position.
   */
  animatePositionChange() {
    // When exiting full-screen mode: focus content
    if (this.prevPosition === ComposerState.Position.FULLSCREEN && this.state.position === ComposerState.Position.NORMAL) {
      this.focus();
      return;
    }

    switch (this.state.position) {
      case ComposerState.Position.HIDDEN:
        return this.hide();
      case ComposerState.Position.MINIMIZED:
        return this.minimize();
      case ComposerState.Position.FULLSCREEN:
        return this.focus();
      case ComposerState.Position.NORMAL:
        return this.show();
    }
  }

  /**
   * Animate the Composer into the new position by changing the height.
   */
  animateHeightChange() {
    const $composer = this.$().stop(true);
    const oldHeight = $composer.outerHeight();
    const scrollTop = $(window).scrollTop();

    $composer.show();
    this.updateHeight();

    const newHeight = $composer.outerHeight();

    if (this.prevPosition === ComposerState.Position.HIDDEN) {
      $composer.css({ bottom: -newHeight, height: newHeight });
    } else {
      $composer.css({ height: oldHeight });
    }

    const animation = $composer.animate({ bottom: 0, height: newHeight }, 'fast').promise();

    this.updateBodyPadding();
    $(window).scrollTop(scrollTop);
    return animation;
  }

  /**
   * Show the Composer backdrop.
   */
  showBackdrop() {
    this.$backdrop = $('<div/>').addClass('composer-backdrop').appendTo('body');
  }

  /**
   * Hide the Composer backdrop.
   */
  hideBackdrop() {
    if (this.$backdrop) this.$backdrop.remove();
  }

  /**
   * Animate the composer sliding up from the bottom to take its normal height.
   *
   * @private
   */
  show() {
    this.animateHeightChange().then(() => this.focus());

    if (app.screen() === 'phone') {
      // On safari fixed position doesn't properly work on mobile,
      // So we use absolute and set the top value.
      // https://github.com/flarum/core/issues/2652

      // Due to another safari bug, `scrollTop` is unreliable when
      // at the very bottom of the page AND opening the composer.
      // So we fallback to a calculated version of scrollTop.
      // https://github.com/flarum/core/issues/2683
      const scrollElement = document.documentElement;
      const topOfViewport = Math.min(scrollElement.scrollTop, scrollElement.scrollHeight - scrollElement.clientHeight);
      this.$().css('top', $('.App').is('.mobile-safari') ? topOfViewport : 0);
      this.showBackdrop();
    }
  }

  /**
   * Animate closing the composer.
   *
   * @private
   */
  hide() {
    const $composer = this.$();

    // Animate the composer sliding down off the bottom edge of the viewport.
    // Only when the animation is completed, update other elements on the page.
    $composer.stop(true).animate({ bottom: -$composer.height() }, 'fast', () => {
      $composer.hide();
      this.hideBackdrop();
      this.updateBodyPadding();
    });
  }

  /**
   * Shrink the composer until only its title is visible.
   *
   * @private
   */
  minimize() {
    this.animateHeightChange();

    this.$().css('top', 'auto');
    this.hideBackdrop();
  }

  /**
   * Build an item list for the composer's controls.
   *
   * @return {ItemList}
   */
  controlItems() {
    const items = new ItemList();

    if (this.state.position === ComposerState.Position.FULLSCREEN) {
      items.add(
        'exitFullScreen',
        ComposerButton.component({
          icon: 'fas fa-compress',
          title: app.translator.trans('core.forum.composer.exit_full_screen_tooltip'),
          onclick: this.state.exitFullScreen.bind(this.state),
        })
      );
    } else {
      if (this.state.position !== ComposerState.Position.MINIMIZED) {
        items.add(
          'minimize',
          ComposerButton.component({
            icon: 'fas fa-minus minimize',
            title: app.translator.trans('core.forum.composer.minimize_tooltip'),
            onclick: this.state.minimize.bind(this.state),
            itemClassName: 'App-backControl',
          })
        );

        items.add(
          'fullScreen',
          ComposerButton.component({
            icon: 'fas fa-expand',
            title: app.translator.trans('core.forum.composer.full_screen_tooltip'),
            onclick: this.state.fullScreen.bind(this.state),
          })
        );
      }

      items.add(
        'close',
        ComposerButton.component({
          icon: 'fas fa-times',
          title: app.translator.trans('core.forum.composer.close_tooltip'),
          onclick: this.state.close.bind(this.state),
        })
      );
    }

    return items;
  }

  /**
   * Initialize default Composer height.
   */
  initializeHeight() {
    this.state.height = localStorage.getItem('composerHeight');

    if (!this.state.height) {
      this.state.height = this.defaultHeight();
    }
  }

  /**
   * Default height of the Composer in case none is saved.
   * @returns {Integer}
   */
  defaultHeight() {
    return this.$().height();
  }

  /**
   * Save a new Composer height and update the DOM.
   * @param {Integer} height
   */
  changeHeight(height) {
    this.state.height = height;
    this.updateHeight();

    localStorage.setItem('composerHeight', this.state.height);
  }
}
