type BootstrapTooltipPlacement = 'top' | 'bottom' | 'left' | 'right';

interface TooltipOptions {
  placement: BootstrapTooltipPlacement;
}

/**
 * This is a backwards compatibility layer to provide support for `$.tooltip()`, since it
 * is used extensively across multiple core and extension files.
 *
 * **Do not use this in new code. Instead add `data-tooltip` and `aria-label="text"`
 * attributes to create a tooltip, or use the `<Tooltip>` component from core if the
 * element already has styles on its `::before` or `::after`.**
 *
 * **Limitations**
 *
 * As this is a simple back-compat layer, it does not implement all features of the original
 * `$.tooltip`. These include:
 *
 * - custom containers (CSS Tooltips do not have this feature)
 * - custom delay
 * - supplying the tooltip text as an option
 * - destroying tooltips (meaningless with the CSS tooltips method)
 *
 * This back-compat layer may be removed in future versions of Flarum.
 *
 * @deprecated
 * @param options Tooltip options
 */
export default function jqueryTooltipCompatLayer(this: JQuery, options: TooltipOptions | 'destroy'): JQuery {
  if (options === 'destroy') {
    // Destroying tooltips is meaningless using the CSS method
    return this;
  }

  const tooltipText = this.attr('title') || this.attr('aria-label');

  if (typeof tooltipText === 'undefined' || tooltipText === '') {
    console.warn(`Called $.tooltip() on element without a title or aria-label attribute.`, this.get(0));
    return this;
  }

  this.attr('data-tooltip', '');
  this.attr('aria-label', tooltipText);
  this.attr('title', null);

  const placement: BootstrapTooltipPlacement | undefined | null = options && options.placement;

  if (placement && placement !== 'top') {
    // Placement manually specified
    this.attr('data-tooltip-position', placement);
  }

  return this;
}
