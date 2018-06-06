# WP Hentry Fixer <small>v0.2.0</small>
<p>Fix hAtom errors appearing in Google Search Console (Search Appearance > Structured Data)</p>

<p><strong>Summary</strong></p>
<ul>
    <li>Fixes missing hentry errors for single posts and archive pages</li>
    <li>Removes hentry classes on standard pages and attachment pages</li>
    <li>Injects hentry data into specified custom post types</li>
    <li>Redirects attachment pages to post parent page or home page if unattached</li>
    <li>Add schema to WooCommerce product pages (injects JSON LD data)</li>
</ul>

<p><strong>Detail Overview</strong></p>
<p><strong>WP Hentry Fixer</strong> allows you to rectify common <em>hAtom errors</em> appearing in Google Search Console (Search Appearance > Structured Data) in the following ways:</p>
<ol>
    <li><strong>Removing "hentry" classes from inapplicable pages</strong> <br>
      like generic pages (<code>is_page()</code>) or attachment pages (<code>is_attachment()</code>) - included by certain themes on occassion</li>
    <li><strong>Inject hentry data into the following types of pages:</strong>
        <ul>
            <li>Archive pages <code>is_archive()</code></li>
            <li>Single Post pages <code>is_single()</code></li>
            <li>Posts page <code>is_home()</code></li>
        </ul>
    </li>
    <li><strong>Inject Hentry Data into Custom Post Types detected</strong> <br>
      injects hentry data into any custom post types detected, e.g: <code>is_singular( 'custom_post_type' )</code></li>
    <li><strong>Redirect attachment pages to post parent</strong> <br>
      redirects <em>attached pages</em> to parent page and <em>unattached pages</em> to home page</li>
    <li><strong>Add schema to WooCommerce product pages</strong> <br>
      injects JSON LD data into single product pages (<code>is_product()</code>), only available if <em>WooCommerce</em> is installed</li>
</ol>
<hr>
License: GNU General Public License v3.0
