/**
 * WordPress dependencies.
 */
const { __ } = wp.i18n;

const { registerPlugin } = wp.plugins;

// wp.editor has the panel since WP 6.6, wp.editPost keeps it for older versions.
const { PluginDocumentSettingPanel } = wp.editor?.PluginDocumentSettingPanel
  ? wp.editor
  : wp.editPost || {};

const { useSelect } = wp.data;

const { useEntityProp } = wp.coreData;

const { PanelRow, BaseControl, TextControl } = wp.components;

function PageOptions() {
  const [meta, setMeta] = useEntityProp('postType', 'docs', 'meta');

  const { postTitle } = useSelect((select) => {
    const { getEditedPostAttribute } = select('core/editor');

    return {
      postTitle: getEditedPostAttribute('title'),
    };
  }, []);

  return (
    <PluginDocumentSettingPanel
      name="DocsPressOptions"
      title={__('DocsPress Options', '@@text_domain')}
    >
      <PanelRow className="docspress-nav-title-metabox">
        <TextControl
          type="text"
          label={__('Nav Title', '@@text_domain')}
          help={__('Optional custom title to display in the docs navigation.', '@@text_domain')}
          value={meta?.nav_title || null}
          placeholder={postTitle}
          onChange={(val) => {
            setMeta({ nav_title: val });
          }}
        />
      </PanelRow>
      <PanelRow className="docspress-helpfullnes-metabox">
        <BaseControl label={__('Helpfulness', '@@text_domain')}>
          <div className="docspress-helpfullnes-metabox-inner">
            <TextControl
              type="number"
              label={__('👍 Positive', '@@text_domain')}
              value={meta?.positive || null}
              onChange={(val) => {
                setMeta({ positive: val });
              }}
              min={0}
            />
            <TextControl
              type="number"
              label={__('👎 Negative', '@@text_domain')}
              value={meta?.negative || null}
              onChange={(val) => {
                setMeta({ negative: val });
              }}
              min={0}
            />
          </div>
        </BaseControl>
      </PanelRow>
    </PluginDocumentSettingPanel>
  );
}

if (PluginDocumentSettingPanel) {
  registerPlugin('docspress-page-options', {
    render: PageOptions,
  });
}
