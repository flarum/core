import Component from '../Component';
import ItemList from '../utils/ItemList';
import listItems from '../helpers/listItems';
import Button from './Button';

import BasicEditorDriver from '../utils/BasicEditorDriver';

/**
 * The `TextEditor` component displays a textarea with controls, including a
 * submit button.
 *
 * ### Attrs
 *
 * - `composer`
 * - `submitLabel`
 * - `value`
 * - `placeholder`
 * - `disabled`
 * - `preview`
 */
export default class TextEditor extends Component {
  oninit(vnode) {
    super.oninit(vnode);

    /**
     * The value of the editor.
     *
     * @type {String}
     */
    this.value = this.attrs.value || '';

    /**
     * Whether the editor is disabled.
     */
    this.disabled = !!this.attrs.disabled;
  }

  view() {
    return (
      <div className="TextEditor">
        <div className="TextEditor-editorContainer"></div>

        <ul className="TextEditor-controls Composer-footer">
          {listItems(this.controlItems().toArray())}
          <li className="TextEditor-toolbar">{this.toolbarItems().toArray()}</li>
        </ul>
      </div>
    );
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    this.attrs.composer.editor = this.buildEditor(this.$('.TextEditor-editorContainer')[0]);

    this.toolbarScrollBarCheckBound = this.toolbarScrollBarCheck.bind(this);

    window.addEventListener('resize', this.toolbarScrollBarCheckBound);
  }

  onupdate() {
    const newDisabled = !!this.attrs.disabled;

    if (this.disabled !== newDisabled) {
      this.disabled = newDisabled;
      this.attrs.composer.editor.disabled(newDisabled);
    }
  }

  onbeforeremove() {
    // Remove event listener to prevent pollution
    window.removeEventListener(this.toolbarScrollBarCheckBound);
  }

  buildEditorParams() {
    return {
      classNames: ['FormControl', 'Composer-flexible', 'TextEditor-editor'],
      disabled: this.disabled,
      placeholder: this.attrs.placeholder || '',
      value: this.value,
      oninput: this.oninput.bind(this),
      inputListeners: [],
      onsubmit: () => {
        this.onsubmit();
        m.redraw();
      },
    };
  }

  buildEditor(dom) {
    return new BasicEditorDriver(dom, this.buildEditorParams());
  }

  /**
   * Build an item list for the text editor controls.
   *
   * @return {ItemList}
   */
  controlItems() {
    const items = new ItemList();

    items.add(
      'submit',
      Button.component(
        {
          icon: 'fas fa-paper-plane',
          className: 'Button Button--primary',
          itemClassName: 'App-primaryControl',
          onclick: this.onsubmit.bind(this),
        },
        this.attrs.submitLabel
      )
    );

    if (this.attrs.preview) {
      items.add(
        'preview',
        Button.component({
          icon: 'far fa-eye',
          className: 'Button Button--icon',
          onclick: this.attrs.preview,
          title: app.translator.trans('core.forum.composer.preview_tooltip'),
          oncreate: (vnode) => $(vnode.dom).tooltip(),
        })
      );
    }

    return items;
  }

  /**
   * Build an item list for the toolbar controls.
   *
   * @return {ItemList}
   */
  toolbarItems() {
    return new ItemList();
  }

  /**
   * Handle input into the textarea.
   *
   * @param {String} value
   */
  oninput(value) {
    this.value = value;

    this.attrs.onchange(this.value);
  }

  /**
   * Handle the submit button being clicked.
   */
  onsubmit() {
    this.attrs.onsubmit(this.value);
  }

  toolbarScrollBarCheck() {
    const toolbarControls = $('.TextEditor-controls')[0];
    const hasScrollbar = toolbarControls.scrollWidth > toolbarControls.clientWidth;
    const className = 'TextEditor-controls--hasScrollbar';

    if (hasScrollbar) {
      toolbarControls.classList.add(className);
    } else {
      toolbarControls.classList.remove(className);
    }
  }
}
