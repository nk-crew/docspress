<?php
/**
 * Vue template.
 *
 * @package @@plugin_name
 */

?>
<div class="wrap" id="docspress-app">
    <h1>
        <?php
        echo esc_html__( 'Documentations', '@@text_domain' );
        ?>
        <a class="page-title-action" href="#" v-on:click.prevent="addDoc">
            <?php echo esc_html__( 'Add Doc', '@@text_domain' ); ?>
        </a>
    </h1>

    <!-- <pre>{{ $data | json }}</pre> -->

    <span class="spinner is-active" style="float: none;"></span>

    <div class="docspress not-loaded">
        <div class="docspress-cat" v-for="(cat, index) in categorized" :data-id="cat.name">
            <h3 v-if="cat.name">{{ cat.name }}</h3>

            <ul v-sortable>
                <li class="docspress-item" v-for="(doc, index) in cat.docs" :data-id="doc.post.id">
                    <h3>
                        <img v-if="doc.post.thumb" :src="doc.post.thumb" :alt="doc.post.title" width="20" height="20">
                        <a v-if="doc.post.caps.edit" target="_blank" :href="editurl + doc.post.id">{{ doc.post.title }}<span v-if="doc.post.status != 'publish'" class="doc-status">{{ doc.post.status }}</span></a>
                        <span v-else>{{ doc.post.title }}<span v-if="doc.post.status != 'publish'" class="doc-status">{{ doc.post.status }}</span></span>

                        <span class="docspress-row-actions">
                            <a target="_blank" :href="viewurl + doc.post.id" title="<?php esc_attr_e( 'Preview the doc', '@@text_domain' ); ?>"><span class="dashicons dashicons-external"></span></a>
                            <span v-if="doc.post.caps.delete" class="docspress-btn-remove" v-on:click="removeDoc(index, docs)" title="<?php esc_attr_e( 'Delete this doc', '@@text_domain' ); ?>"><span class="dashicons dashicons-trash"></span></span>
                        </span>
                    </h3>

                    <div class="docspress-item-inner">
                        <ul class="sections" v-sortable>
                            <li v-for="(section, index) in doc.child" :data-id="section.post.id">
                                <span class="section-title" v-on:click="toggleCollapse">
                                    <a v-if="section.post.caps.edit" target="_blank" :href="editurl + section.post.id">{{ section.post.title }}<span v-if="section.post.status != 'publish'" class="doc-status">{{ section.post.status }}</span> <span v-if="section.child.length > 0" class="count">{{ section.child.length }}</span></a>
                                    <span v-else>{{ section.post.title }}<span v-if="section.post.status != 'publish'" class="doc-status">{{ section.post.status }}</span> <span v-if="section.child.length > 0" class="count">{{ section.child.length }}</span></span>

                                    <span class="actions docspress-row-actions">
                                        <a target="_blank" :href="viewurl + section.post.id" title="<?php esc_attr_e( 'Preview the section', '@@text_domain' ); ?>"><span class="dashicons dashicons-external"></span></a>
                                        <span class="docspress-btn-remove" v-if="section.post.caps.delete" v-on:click="removeSection(index, doc.child)" title="<?php esc_attr_e( 'Delete this section', '@@text_domain' ); ?>"><span class="dashicons dashicons-trash"></span></span>
                                        <span class="add-article" v-on:click="addArticle(section,$event)" title="<?php esc_attr_e( 'Add a new article', '@@text_domain' ); ?>"><span class="dashicons dashicons-plus-alt"></span></span>
                                    </span>
                                </span>

                                <ul class="articles collapsed connectedSortable" v-if="section.child" v-sortable>
                                    <li class="article" v-for="(article, index) in section.child" :data-id="article.post.id">
                                        <a v-if="article.post.caps.edit" target="_blank" :href="editurl + article.post.id">{{ article.post.title }}<span v-if="article.post.status != 'publish'" class="doc-status">{{ article.post.status }}</span> <span v-if="article.child.length > 0" class="count">{{ article.child.length }}</span></a>
                                        <span v-else>{{ article.post.title }}</span>

                                        <span class="actions docspress-row-actions">
                                            <a target="_blank" :href="viewurl + article.post.id" title="<?php esc_attr_e( 'Preview the article', '@@text_domain' ); ?>"><span class="dashicons dashicons-external"></span></a>
                                            <span class="docspress-btn-remove" v-if="article.post.caps.delete" v-on:click="removeArticle(index, section.child)" title="<?php esc_attr_e( 'Delete this article', '@@text_domain' ); ?>"><span class="dashicons dashicons-trash"></span></span>
                                            <span class="add-article" v-on:click="addArticle(article, $event)" title="<?php esc_attr_e( 'Add a new article', '@@text_domain' ); ?>"><span class="dashicons dashicons-plus-alt"></span></span>
                                        </span>

                                        <ul class="articles connectedSortable" v-if="article.child" v-sortable>
                                            <li class="article" v-for="(article_child, index) in article.child" :data-id="article_child.post.id">
                                                <a v-if="article_child.post.caps.edit" target="_blank" :href="editurl + article_child.post.id">{{ article_child.post.title }}<span v-if="article_child.post.status != 'publish'" class="doc-status">{{ article_child.post.status }}</span></a>
                                                <span v-else>{{ article_child.post.title }}</span>

                                                <span class="actions docspress-row-actions">
                                                    <a target="_blank" :href="viewurl + article_child.post.id" title="<?php esc_attr_e( 'Preview the article', '@@text_domain' ); ?>"><span class="dashicons dashicons-external"></span></a>
                                                    <span class="docspress-btn-remove" v-if="article_child.post.caps.delete" v-on:click="removeArticle(index, article.child)" title="<?php esc_attr_e( 'Delete this article', '@@text_domain' ); ?>"><span class="dashicons dashicons-trash"></span></span>
                                                </span>
                                            </li>
                                        </ul>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                        <a class="docspress-add-section-btn" href="#" v-on:click.prevent="addSection(doc)"><span class="dashicons dashicons-plus"></span></a>
                    </div>

                    <div class="docspress-actions">
                        <a class="button" href="#" v-on:click.prevent="cloneDoc(doc)"><?php echo esc_html__( 'Clone', '@@text_domain' ); ?></a>
                        <a class="button" href="#" v-on:click.prevent="exportDoc(doc)"><?php echo esc_html__( 'Export as HTML', '@@text_domain' ); ?></a>
                    </div>
                </li>
            </ul>
        </div>
    </div>

    <div class="no-docspress not-loaded" v-show="!docs.length">
        <?php
        // translators: %s - link.
        printf( esc_html__( 'No documentations has been found. Perhaps %s?', '@@text_domain' ), '<a href="#" v-on:click.prevent="addDoc">' . esc_html__( 'create one', '@@text_domain' ) . '</a>' );
        ?>
    </div>

</div>
