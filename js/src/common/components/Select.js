import Component from '../Component';
import icon from '../helpers/icon';
import withAttr from '../utils/withAttr';
import classList from '../utils/classList';

/**
 * The `Select` component displays a <select> input, surrounded with some extra
 * elements for styling. It accepts the following attrs:
 *
 * - `options` A map of option values to labels.
 * - `onchange` A callback to run when the selected value is changed.
 * - `value` The value of the selected option.
 * - `disabled` Disabled state for the input.
 * - `outerClassName` class given to the containing Select element
 *
 * Other attributes are passed directly to the `<select>` element rendered to the DOM.
 */
export default class Select extends Component {
  view() {
    const { options, onchange, value, disabled, outerClassName, ...domAttrs } = this.attrs;

    return (
      <span className={classList('Select', outerClassName)}>
        <select
          className="Select-input FormControl"
          onchange={onchange ? withAttr('value', onchange.bind(this)) : undefined}
          value={value}
          disabled={disabled}
          {...domAttrs}
        >
          {Object.keys(options).map((key) => (
            <option value={key}>{options[key]}</option>
          ))}
        </select>
        {icon('fas fa-sort', { className: 'Select-caret' })}
      </span>
    );
  }
}
