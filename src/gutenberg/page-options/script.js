/**
 * WordPress dependencies.
 */
const { __ } = wp.i18n;

const { registerPlugin } = wp.plugins;

const { PluginDocumentSettingPanel } = wp.editPost || {};

const { useEntityProp } = wp.coreData;

const { PanelRow, BaseControl, TextControl } = wp.components;

function PageOptions() {
  const [meta, setMeta] = useEntityProp('postType', 'docs', 'meta');

  return (
    <PluginDocumentSettingPanel
      name="DocsPressHelpfulness"
      title={__('Helpfulness', '@@text_domain')}
    >
      <PanelRow className="docspress-helpfullnes-metabox">
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
      </PanelRow>
    </PluginDocumentSettingPanel>
  );
}

if (PluginDocumentSettingPanel) {
  registerPlugin('docspress-page-options', {
    render: PageOptions,
  });
}
