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
                            <svg class="docspress-icon docspress-icon-more" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 13C12.5523 13 13 12.5523 13 12C13 11.4477 12.5523 11 12 11C11.4477 11 11 11.4477 11 12C11 12.5523 11.4477 13 12 13Z" fill="#E0E0E0" stroke="#E0E0E0" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M19 13C19.5523 13 20 12.5523 20 12C20 11.4477 19.5523 11 19 11C18.4477 11 18 11.4477 18 12C18 12.5523 18.4477 13 19 13Z" fill="#E0E0E0" stroke="#E0E0E0" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M5 13C5.55228 13 6 12.5523 6 12C6 11.4477 5.55228 11 5 11C4.44772 11 4 11.4477 4 12C4 12.5523 4.44772 13 5 13Z" fill="#E0E0E0" stroke="#E0E0E0" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <a target="_blank" :href="viewurl + doc.post.id" title="<?php esc_attr_e( 'Preview the doc', '@@text_domain' ); ?>">
                                <svg class="docspress-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z" />
                                    <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z" />
                                </svg>
                            </a>
                            <span v-if="doc.post.caps.delete" class="docspress-btn-remove" v-on:click="removeDoc(doc.post.id)" title="<?php esc_attr_e( 'Delete this doc', '@@text_domain' ); ?>">
                                <svg class="docspress-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </span>
                    </h3>

                    <div class="docspress-item-inner">
                        <ul class="sections" v-sortable>
                            <li v-for="(section, index) in doc.child" :data-id="section.post.id">
                                <span class="section-title">
                                    <span class="section-toggle" v-if="section.child.length > 0" v-on:click="toggleCollapse">
                                        <svg class="docspress-icon" width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M5.00002 1.66666V8.33333M1.66669 5H8.33335" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        <svg class="docspress-icon" width="10" height="2" viewBox="0 0 10 2" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M1.66669 1H8.33335" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </span>

                                    <a v-if="section.post.caps.edit" target="_blank" :href="editurl + section.post.id">
                                        {{ section.post.title }}
                                        <span v-if="section.post.status != 'publish'" class="doc-status">{{ section.post.status }}</span>
                                        <span v-if="section.child.length > 0" class="count">{{ section.child.length }}</span>
                                    </a>
                                    <span v-else>
                                        {{ section.post.title }}
                                        <span v-if="section.post.status != 'publish'" class="doc-status">{{ section.post.status }}</span>
                                        <span v-if="section.child.length > 0" class="count">{{ section.child.length }}</span>
                                    </span>

                                    <span class="actions docspress-row-actions">
                                        <svg class="docspress-icon docspress-icon-more" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M12 13C12.5523 13 13 12.5523 13 12C13 11.4477 12.5523 11 12 11C11.4477 11 11 11.4477 11 12C11 12.5523 11.4477 13 12 13Z" fill="#E0E0E0" stroke="#E0E0E0" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M19 13C19.5523 13 20 12.5523 20 12C20 11.4477 19.5523 11 19 11C18.4477 11 18 11.4477 18 12C18 12.5523 18.4477 13 19 13Z" fill="#E0E0E0" stroke="#E0E0E0" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M5 13C5.55228 13 6 12.5523 6 12C6 11.4477 5.55228 11 5 11C4.44772 11 4 11.4477 4 12C4 12.5523 4.44772 13 5 13Z" fill="#E0E0E0" stroke="#E0E0E0" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        <a target="_blank" :href="viewurl + section.post.id" title="<?php esc_attr_e( 'Preview the section', '@@text_domain' ); ?>">
                                            <svg class="docspress-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z" />
                                                <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z" />
                                            </svg>
                                        </a>
                                        <span class="docspress-btn-remove" v-if="section.post.caps.delete" v-on:click="removeSection(section.post.id)" title="<?php esc_attr_e( 'Delete this section', '@@text_domain' ); ?>">
                                            <svg class="docspress-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                        </span>
                                        <span class="add-article" v-on:click="addArticle(section,$event)" title="<?php esc_attr_e( 'Add a new article', '@@text_domain' ); ?>">
                                            <svg class="docspress-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd" />
                                            </svg>
                                        </span>
                                    </span>
                                </span>

                                <ul class="articles collapsed connectedSortable" v-if="section.child" v-sortable>
                                    <li class="article" v-for="(article, index) in section.child" :data-id="article.post.id">
                                        <a v-if="article.post.caps.edit" target="_blank" :href="editurl + article.post.id">{{ article.post.title }}<span v-if="article.post.status != 'publish'" class="doc-status">{{ article.post.status }}</span> <span v-if="article.child.length > 0" class="count">{{ article.child.length }}</span></a>
                                        <span v-else>{{ article.post.title }}</span>

                                        <span class="actions docspress-row-actions">
                                            <svg class="docspress-icon docspress-icon-more" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M12 13C12.5523 13 13 12.5523 13 12C13 11.4477 12.5523 11 12 11C11.4477 11 11 11.4477 11 12C11 12.5523 11.4477 13 12 13Z" fill="#E0E0E0" stroke="#E0E0E0" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M19 13C19.5523 13 20 12.5523 20 12C20 11.4477 19.5523 11 19 11C18.4477 11 18 11.4477 18 12C18 12.5523 18.4477 13 19 13Z" fill="#E0E0E0" stroke="#E0E0E0" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M5 13C5.55228 13 6 12.5523 6 12C6 11.4477 5.55228 11 5 11C4.44772 11 4 11.4477 4 12C4 12.5523 4.44772 13 5 13Z" fill="#E0E0E0" stroke="#E0E0E0" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            <a target="_blank" :href="viewurl + article.post.id" title="<?php esc_attr_e( 'Preview the article', '@@text_domain' ); ?>">
                                                <svg class="docspress-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z" />
                                                    <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z" />
                                                </svg>
                                            </a>
                                            <span class="docspress-btn-remove" v-if="article.post.caps.delete" v-on:click="removeArticle(article.post.id)" title="<?php esc_attr_e( 'Delete this article', '@@text_domain' ); ?>">
                                                <svg class="docspress-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                </svg>
                                            </span>
                                            <span class="add-article" v-on:click="addArticle(article, $event)" title="<?php esc_attr_e( 'Add a new article', '@@text_domain' ); ?>">
                                                <svg class="docspress-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd" />
                                                </svg>
                                            </span>
                                        </span>

                                        <ul class="articles connectedSortable" v-if="article.child" v-sortable>
                                            <li class="article" v-for="(article_child, index) in article.child" :data-id="article_child.post.id">
                                                <a v-if="article_child.post.caps.edit" target="_blank" :href="editurl + article_child.post.id">{{ article_child.post.title }}<span v-if="article_child.post.status != 'publish'" class="doc-status">{{ article_child.post.status }}</span></a>
                                                <span v-else>{{ article_child.post.title }}</span>

                                                <span class="actions docspress-row-actions">
                                                    <svg class="docspress-icon docspress-icon-more" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M12 13C12.5523 13 13 12.5523 13 12C13 11.4477 12.5523 11 12 11C11.4477 11 11 11.4477 11 12C11 12.5523 11.4477 13 12 13Z" fill="#E0E0E0" stroke="#E0E0E0" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                        <path d="M19 13C19.5523 13 20 12.5523 20 12C20 11.4477 19.5523 11 19 11C18.4477 11 18 11.4477 18 12C18 12.5523 18.4477 13 19 13Z" fill="#E0E0E0" stroke="#E0E0E0" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                        <path d="M5 13C5.55228 13 6 12.5523 6 12C6 11.4477 5.55228 11 5 11C4.44772 11 4 11.4477 4 12C4 12.5523 4.44772 13 5 13Z" fill="#E0E0E0" stroke="#E0E0E0" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                    </svg>
                                                    <a target="_blank" :href="viewurl + article_child.post.id" title="<?php esc_attr_e( 'Preview the article', '@@text_domain' ); ?>">
                                                        <svg class="docspress-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                            <path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z" />
                                                            <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z" />
                                                        </svg>
                                                    </a>
                                                    <span class="docspress-btn-remove" v-if="article_child.post.caps.delete" v-on:click="removeArticle(article.post.id)" title="<?php esc_attr_e( 'Delete this article', '@@text_domain' ); ?>">
                                                        <svg class="docspress-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                        </svg>
                                                    </span>
                                                </span>
                                            </li>
                                        </ul>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                        <a class="docspress-add-section-btn" href="#" v-on:click.prevent="addSection(doc)">
                            <svg class="docspress-icon" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M15.8333 2.5H4.16667C3.24619 2.5 2.5 3.24619 2.5 4.16667V15.8333C2.5 16.7538 3.24619 17.5 4.16667 17.5H15.8333C16.7538 17.5 17.5 16.7538 17.5 15.8333V4.16667C17.5 3.24619 16.7538 2.5 15.8333 2.5Z" fill="currentColor" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M10 6.66666V13.3333M6.66669 9.99999H13.3334" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <?php echo esc_html__( 'Add new section', '@@text_domain' ); ?>
                        </a>
                    </div>

                    <div class="docspress-actions">
                        <a class="button" href="#" v-on:click.prevent="cloneDoc(doc)">
                            <svg class="docspress-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M7 9a2 2 0 012-2h6a2 2 0 012 2v6a2 2 0 01-2 2H9a2 2 0 01-2-2V9z" />
                                <path d="M5 3a2 2 0 00-2 2v6a2 2 0 002 2V5h8a2 2 0 00-2-2H5z" />
                            </svg>
                            <?php echo esc_html__( 'Clone', '@@text_domain' ); ?>
                        </a>
                        <a class="button" href="#" v-on:click.prevent="exportDoc(doc)">
                            <svg class="docspress-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v3.586L7.707 9.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 10.586V7z" clip-rule="evenodd" />
                            </svg>
                            <?php echo esc_html__( 'Export as HTML', '@@text_domain' ); ?>
                        </a>
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
