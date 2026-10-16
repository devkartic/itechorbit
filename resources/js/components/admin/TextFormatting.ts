import { Mark } from '@tiptap/react';

export const fontSizes = ['14', '16', '18', '20', '24', '32'];
export const fontFamilies = ['sans', 'serif', 'mono'];

// Bounded data attributes keep formatting portable without allowing arbitrary CSS.
export const TextFormatting = Mark.create({
  name: 'textFormatting',
  addAttributes() {
    return {
      size: { default: null, parseHTML: element => fontSizes.includes(element.dataset.fontSize ?? '') ? element.dataset.fontSize : null, renderHTML: attributes => fontSizes.includes(attributes.size) ? { 'data-font-size': attributes.size } : {} },
      font: { default: null, parseHTML: element => fontFamilies.includes(element.dataset.textFont ?? '') ? element.dataset.textFont : null, renderHTML: attributes => fontFamilies.includes(attributes.font) ? { 'data-text-font': attributes.font } : {} },
    };
  },
  parseHTML: () => [{ tag: 'span[data-font-size]' }, { tag: 'span[data-text-font]' }],
  renderHTML: ({ HTMLAttributes }) => ['span', HTMLAttributes, 0],
});
