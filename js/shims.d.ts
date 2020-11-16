// Mithril
import Mithril from 'mithril';

// Other third-party libs
import * as _dayjs from 'dayjs';
import * as _$ from 'jquery';
import * as _ColorThief from 'color-thief-browser';

// Globals from flarum/core
import Application from './src/common/Application';

/**
 * flarum/core exposes several extensions globally:
 *
 * - jQuery for convenient DOM manipulation
 * - Mithril for VDOM and components
 * - dayjs for date/time operations
 *
 * Since these are already part of the global namespace, extensions won't need
 * to (and should not) bundle these themselves.
 */
declare global {
  const $: typeof _$;
  const m: Mithril.Static;
  const dayjs: typeof _dayjs;
  const ColorThief: _ColorThief;
}

/**
 * All global variables owned by flarum/core.
 */
declare global {
  const app: Application;
}
