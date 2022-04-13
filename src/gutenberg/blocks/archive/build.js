const { registerBlockType } = wp.blocks;
const { serverSideRender: ServerSideRender } = wp;

const { Fragment } = wp.element;

registerBlockType( 'docspress/archive', {
    title: 'DocsPress Archive',
    description: 'DocsPress Archive',
    icon: ( <svg version="1.1" x="0px" y="0px" width="24px" height="24px" viewBox="0 0 24 24"><rect width="24" height="24" style={ { fill: 'rgb(0,121,200)' } } /></svg> ),
    edit: () => {
        return <Fragment>
            <ServerSideRender
                block="docspress/archive"
                attributes={ {} }
            />
        </Fragment>;
    },
    save() {
        return null; // Nothing to save here..
    },
} );
