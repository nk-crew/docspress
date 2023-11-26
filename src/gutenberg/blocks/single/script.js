import metadata from './block.json';

const { registerBlockType } = wp.blocks;
const { useBlockProps } = wp.blockEditor;
const { Placeholder } = wp.components;

registerBlockType(metadata.name, {
  icon: 'media-document',
  edit() {
    const blockProps = useBlockProps();

    return (
      <div {...blockProps}>
        <Placeholder
          icon="media-document"
          label="DocsPress Single Article Block"
          instructions="This is an editor placeholder for the DocsPress Single Article Block. In your documentation this will be replaced by the template and display with your article image(s), title, etc. You can move this placeholder around and add further blocks around it to extend the template."
        />
      </div>
    );
  },
  save() {
    return null; // Nothing to save here..
  },
});
