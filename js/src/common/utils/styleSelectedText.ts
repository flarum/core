/*
 * Original Copyright GitHub, Inc. Licensed under the MIT License.
 * See license text at https://github.com/github/markdown-toolbar-element/blob/master/LICENSE.
 */

import insertText, { SelectionRange } from './insertText';

interface StyleArgs {
  prefix: string;
  suffix: string;
  blockPrefix: string;
  blockSuffix: string;
  multiline: boolean;
  replaceNext: string;
  prefixSpace: boolean;
  scanFor: string;
  surroundWithNewlines: boolean;
  orderedList: boolean;
  trimFirst: boolean;
}

const defaults: StyleArgs = {
  prefix: '',
  suffix: '',
  blockPrefix: '',
  blockSuffix: '',
  multiline: false,
  replaceNext: '',
  prefixSpace: false,
  scanFor: '',
  surroundWithNewlines: false,
  orderedList: false,
  trimFirst: false,
};

export default function styleSelectedText(textarea: HTMLTextAreaElement, styleArgs: StyleArgs) {
  // Next 2 lines are added
  textarea.focus();
  styleArgs = Object.assign({}, defaults, styleArgs);
  // Prev 2 lines are added
  const text = textarea.value.slice(textarea.selectionStart, textarea.selectionEnd);

  let result;
  if (styleArgs.orderedList) {
    result = orderedList(textarea);
  } else if (styleArgs.multiline && isMultipleLines(text)) {
    result = multilineStyle(textarea, styleArgs);
  } else {
    result = blockStyle(textarea, styleArgs);
  }

  insertText(textarea, result);
}

function isMultipleLines(string: string): boolean {
  return string.trim().split('\n').length > 1;
}

function repeat(string: string, n: number): string {
  return Array(n + 1).join(string);
}

function wordSelectionStart(text: string, i: number): number {
  let index = i;
  while (text[index] && text[index - 1] != null && !text[index - 1].match(/\s/)) {
    index--;
  }
  return index;
}

function wordSelectionEnd(text: string, i: number, multiline: boolean): number {
  let index = i;
  const breakpoint = multiline ? /\n/ : /\s/;
  while (text[index] && !text[index].match(breakpoint)) {
    index++;
  }
  return index;
}

function expandSelectedText(textarea: HTMLTextAreaElement, prefixToUse: string, suffixToUse: string, multiline = false): string {
  if (textarea.selectionStart === textarea.selectionEnd) {
    textarea.selectionStart = wordSelectionStart(textarea.value, textarea.selectionStart);
    textarea.selectionEnd = wordSelectionEnd(textarea.value, textarea.selectionEnd, multiline);
  } else {
    const expandedSelectionStart = textarea.selectionStart - prefixToUse.length;
    const expandedSelectionEnd = textarea.selectionEnd + suffixToUse.length;
    const beginsWithPrefix = textarea.value.slice(expandedSelectionStart, textarea.selectionStart) === prefixToUse;
    const endsWithSuffix = textarea.value.slice(textarea.selectionEnd, expandedSelectionEnd) === suffixToUse;
    if (beginsWithPrefix && endsWithSuffix) {
      textarea.selectionStart = expandedSelectionStart;
      textarea.selectionEnd = expandedSelectionEnd;
    }
  }
  return textarea.value.slice(textarea.selectionStart, textarea.selectionEnd);
}

interface Newlines {
  newlinesToAppend: string;
  newlinesToPrepend: string;
}

function newlinesToSurroundSelectedText(textarea: HTMLTextAreaElement): Newlines {
  const beforeSelection = textarea.value.slice(0, textarea.selectionStart);
  const afterSelection = textarea.value.slice(textarea.selectionEnd);

  const breaksBefore = beforeSelection.match(/\n*$/);
  const breaksAfter = afterSelection.match(/^\n*/);
  const newlinesBeforeSelection = breaksBefore ? breaksBefore[0].length : 0;
  const newlinesAfterSelection = breaksAfter ? breaksAfter[0].length : 0;

  let newlinesToAppend;
  let newlinesToPrepend;

  if (beforeSelection.match(/\S/) && newlinesBeforeSelection < 2) {
    newlinesToAppend = repeat('\n', 2 - newlinesBeforeSelection);
  }

  if (afterSelection.match(/\S/) && newlinesAfterSelection < 2) {
    newlinesToPrepend = repeat('\n', 2 - newlinesAfterSelection);
  }

  if (newlinesToAppend == null) {
    newlinesToAppend = '';
  }

  if (newlinesToPrepend == null) {
    newlinesToPrepend = '';
  }

  return { newlinesToAppend, newlinesToPrepend };
}

function blockStyle(textarea: HTMLTextAreaElement, arg: StyleArgs): SelectionRange {
  let newlinesToAppend;
  let newlinesToPrepend;

  const { prefix, suffix, blockPrefix, blockSuffix, replaceNext, prefixSpace, scanFor, surroundWithNewlines } = arg;
  const originalSelectionStart = textarea.selectionStart;
  const originalSelectionEnd = textarea.selectionEnd;

  let selectedText = textarea.value.slice(textarea.selectionStart, textarea.selectionEnd);
  let prefixToUse = isMultipleLines(selectedText) && blockPrefix.length > 0 ? `${blockPrefix}\n` : prefix;
  let suffixToUse = isMultipleLines(selectedText) && blockSuffix.length > 0 ? `\n${blockSuffix}` : prefixToUse === prefix ? suffix : '';

  if (prefixSpace) {
    const beforeSelection = textarea.value[textarea.selectionStart - 1];
    if (textarea.selectionStart !== 0 && beforeSelection != null && !beforeSelection.match(/\s/)) {
      prefixToUse = ` ${prefixToUse}`;
    }
  }
  selectedText = expandSelectedText(textarea, prefixToUse, suffixToUse, arg.multiline);
  let selectionStart = textarea.selectionStart;
  let selectionEnd = textarea.selectionEnd;
  const hasReplaceNext = replaceNext.length > 0 && suffixToUse.indexOf(replaceNext) > -1 && selectedText.length > 0;
  if (surroundWithNewlines) {
    const ref = newlinesToSurroundSelectedText(textarea);
    newlinesToAppend = ref.newlinesToAppend;
    newlinesToPrepend = ref.newlinesToPrepend;
    prefixToUse = newlinesToAppend + prefix;
    suffixToUse += newlinesToPrepend;
  }

  if (selectedText.startsWith(prefixToUse) && selectedText.endsWith(suffixToUse)) {
    const replacementText = selectedText.slice(prefixToUse.length, selectedText.length - suffixToUse.length);
    if (originalSelectionStart === originalSelectionEnd) {
      let position = originalSelectionStart - prefixToUse.length;
      position = Math.max(position, selectionStart);
      position = Math.min(position, selectionStart + replacementText.length);
      selectionStart = selectionEnd = position;
    } else {
      selectionEnd = selectionStart + replacementText.length;
    }
    return { text: replacementText, selectionStart, selectionEnd };
  } else if (!hasReplaceNext) {
    let replacementText = prefixToUse + selectedText + suffixToUse;
    selectionStart = originalSelectionStart + prefixToUse.length;
    selectionEnd = originalSelectionEnd + prefixToUse.length;
    const whitespaceEdges = selectedText.match(/^\s*|\s*$/g);
    if (arg.trimFirst && whitespaceEdges) {
      const leadingWhitespace = whitespaceEdges[0] || '';
      const trailingWhitespace = whitespaceEdges[1] || '';
      replacementText = leadingWhitespace + prefixToUse + selectedText.trim() + suffixToUse + trailingWhitespace;
      selectionStart += leadingWhitespace.length;
      selectionEnd -= trailingWhitespace.length;
    }
    return { text: replacementText, selectionStart, selectionEnd };
  } else if (scanFor.length > 0 && selectedText.match(scanFor)) {
    suffixToUse = suffixToUse.replace(replaceNext, selectedText);
    const replacementText = prefixToUse + suffixToUse;
    selectionStart = selectionEnd = selectionStart + prefixToUse.length;
    return { text: replacementText, selectionStart, selectionEnd };
  } else {
    const replacementText = prefixToUse + selectedText + suffixToUse;
    selectionStart = selectionStart + prefixToUse.length + selectedText.length + suffixToUse.indexOf(replaceNext);
    selectionEnd = selectionStart + replaceNext.length;
    return { text: replacementText, selectionStart, selectionEnd };
  }
}

function multilineStyle(textarea: HTMLTextAreaElement, arg: StyleArgs) {
  const { prefix, suffix, blockPrefix, blockSuffix, surroundWithNewlines } = arg;
  let text = textarea.value.slice(textarea.selectionStart, textarea.selectionEnd);
  let selectionStart = textarea.selectionStart;
  let selectionEnd = textarea.selectionEnd;
  const lines = text.split('\n');
  let prefixToUse = blockPrefix.length > 0 ? blockPrefix : prefix;
  let suffixToUse = blockSuffix.length > 0 ? blockSuffix : prefixToUse == prefix ? suffix : '';
  const undoStyle = lines.every((line) => line.startsWith(prefixToUse) && line.endsWith(suffixToUse));

  if (undoStyle) {
    text = lines.map((line) => line.slice(prefixToUse.length, line.length - suffixToUse.length)).join('\n');
    selectionEnd = selectionStart + text.length;
  } else {
    text = lines.map((line) => prefixToUse + line + suffixToUse).join('\n');
    if (surroundWithNewlines || suffixToUse === '') {
      const { newlinesToAppend, newlinesToPrepend } = newlinesToSurroundSelectedText(textarea);
      selectionStart += newlinesToAppend.length;
      selectionEnd = selectionStart + text.length;
      text = newlinesToAppend + text + newlinesToPrepend;
    }
  }

  return { text, selectionStart, selectionEnd };
}

function orderedList(textarea: HTMLTextAreaElement): SelectionRange {
  const orderedListRegex = /^\d+\.\s+/;
  const noInitialSelection = textarea.selectionStart === textarea.selectionEnd;
  let selectionEnd;
  let selectionStart;
  let text = textarea.value.slice(textarea.selectionStart, textarea.selectionEnd);
  let textToUnstyle = text;
  let lines = text.split('\n');
  let startOfLine, endOfLine;
  if (noInitialSelection) {
    const linesBefore = textarea.value.slice(0, textarea.selectionStart).split(/\n/);
    startOfLine = textarea.selectionStart - linesBefore[linesBefore.length - 1].length;
    endOfLine = wordSelectionEnd(textarea.value, textarea.selectionStart, true);
    textToUnstyle = textarea.value.slice(startOfLine, endOfLine);
  }
  const linesToUnstyle = textToUnstyle.split('\n');
  const undoStyling = linesToUnstyle.every((line) => orderedListRegex.test(line));

  if (undoStyling) {
    lines = linesToUnstyle.map((line) => line.replace(orderedListRegex, ''));
    text = lines.join('\n');
    if (noInitialSelection && startOfLine && endOfLine) {
      const lengthDiff = linesToUnstyle[0].length - lines[0].length;
      selectionStart = selectionEnd = textarea.selectionStart - lengthDiff;
      textarea.selectionStart = startOfLine;
      textarea.selectionEnd = endOfLine;
    }
  } else {
    lines = numberedLines(lines);
    text = lines.join('\n');
    const { newlinesToAppend, newlinesToPrepend } = newlinesToSurroundSelectedText(textarea);
    selectionStart = textarea.selectionStart + newlinesToAppend.length;
    selectionEnd = selectionStart + text.length;
    if (noInitialSelection) selectionStart = selectionEnd;
    text = newlinesToAppend + text + newlinesToPrepend;
  }

  return { text, selectionStart, selectionEnd };
}

function numberedLines(lines: string[]) {
  let i;
  let len;
  let index;
  const results = [];
  for (index = i = 0, len = lines.length; i < len; index = ++i) {
    const line = lines[index];
    results.push(`${index + 1}. ${line}`);
  }
  return results;
}
