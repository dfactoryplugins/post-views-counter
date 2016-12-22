<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Post_Views_Counter_Crawler_Detect class.
 * 
 * Based on CrawlerDetect php class adjusted to PHP 5.2
 * https://github.com/JayBizzle/Crawler-Detect/blob/master/src/CrawlerDetect.php
 * 
 * @since 1.2.4
 * @class Post_Views_Counter_Crawler_Detect
 */
class Post_Views_Counter_Crawler_Detect {

	/**
	 * The user agent.
	 *
	 * @var null
	 */
	protected $user_agent = null;

	/**
	 * Headers that contain a user agent.
	 *
	 * @var array
	 */
	protected $http_headers = array();

	/**
	 * Store regex matches.
	 *
	 * @var array
	 */
	protected $matches = array();

	/**
	 * Crawlers object.
	 *
	 * @var object
	 */
	protected $crawlers;

	/**
	 * Exclusions object.
	 *
	 * @var object
	 */
	protected $exclusions;

	/**
	 * Headers object.
	 *
	 * @var object
	 */
	protected $ua_http_headers;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_action( 'after_setup_theme', array( $this, 'init' ) );
	}

	/**
	 * Initialize class.
	 */
	public function init() {
		// break on admin side
		if ( is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) )
			return;

		$this->crawlers = $this->get_crawlers_list();
		$this->exclusions = $this->get_exclusions_list();
		$this->ua_http_headers = $this->get_headers_list();
		$this->set_http_headers();
		$this->set_user_agent();
	}

	/**
	 * Set HTTP headers.
	 *
	 * @param array $http_headers
	 */
	public function set_http_headers( $http_headers = null ) {
		// use global _SERVER if $http_headers aren't defined
		if ( ! is_array( $http_headers ) || ! count( $http_headers ) ) {
			$http_headers = $_SERVER;
		}
		// clear existing headers
		$this->http_headers = array();
		// only save HTTP headers - in PHP land, that means only _SERVER vars that start with HTTP_.
		foreach ( $http_headers as $key => $value ) {
			if ( substr( $key, 0, 5 ) === 'HTTP_' ) {
				$this->http_headers[$key] = $value;
			}
		}
	}

	/**
	 * Return user agent headers.
	 *
	 * @return array
	 */
	public function get_ua_http_headers() {
		return $this->ua_http_headers;
	}

	/**
	 * Return the user agent.
	 *
	 * @return string
	 */
	public function get_user_agent() {
		return $this->user_agent;
	}

	/**
	 * Set the user agent.
	 *
	 * @param string $user_agent
	 */
	public function set_user_agent( $user_agent = null ) {
		if ( false === empty( $user_agent ) ) {
			return $this->user_agent = $user_agent;
		} else {
			$this->user_agent = null;
			foreach ( $this->get_ua_http_headers() as $alt_header ) {
				if ( false === empty( $this->http_headers[$alt_header] ) ) { // @todo: should use get_http_header(), but it would be slow.
					$this->user_agent .= $this->http_headers[$alt_header] . ' ';
				}
			}
			return $this->user_agent = ( ! empty( $this->user_agent ) ? trim( $this->user_agent ) : null);
		}
	}

	/**
	 * Build the user agent regex.
	 *
	 * @return string
	 */
	public function get_regex() {
		return '(' . implode( '|', $this->crawlers ) . ')';
	}

	/**
	 * Build the replacement regex.
	 *
	 * @return string
	 */
	public function get_exclusions() {
		return '(' . implode( '|', $this->exclusions ) . ')';
	}

	/**
	 * Check user agent string against the regex.
	 *
	 * @param string $user_agent
	 *
	 * @return bool
	 */
	public function is_crawler( $user_agent = null ) {
		$agent = is_null( $user_agent ) ? $this->user_agent : $user_agent;
		$agent = preg_replace( '/' . $this->get_exclusions() . '/i', '', $agent );
		if ( strlen( trim( $agent ) ) == 0 ) {
			return false;
		} else {
			$result = preg_match( '/' . $this->get_regex() . '/i', trim( $agent ), $matches );
		}
		if ( $matches ) {
			$this->matches = $matches;
		}
		return (bool) $result;
	}

	/**
	 * Return the matches.
	 *
	 * @return string
	 */
	public function get_matches() {
		return isset( $this->matches[0] ) ? $this->matches[0] : null;
	}

	/**
	 * Return the regular expressions to match against the user agent.
	 *
	 * @return array
	 */
	protected function get_crawlers_list() {
		$data = array(
			'.*Java.*outbrain',
			'008\/',
			'192.comAgent',
			'2ip\.ru',
			'404checker',
			'^bluefish ',
			'^FDM ',
			'^Goose\/',
			'^Java\/',
			'^Mget',
			'^NG\/[0-9\.]',
			'^NING\/',
			'^PHP\/[0-9]',
			'^RMA\/',
			'^Ruby|Ruby\/[0-9]',
			'^scrutiny\/',
			'^VSE\/[0-9]',
			'^WordPress\.com',
			'^XRL\/[0-9]',
			'a3logics\.in',
			'A6-Indexer',
			'a\.pr-cy\.ru',
			'Aboundex',
			'aboutthedomain',
			'Accoona-AI-Agent',
			'acoon',
			'acrylicapps\.com\/pulp',
			'adbeat',
			'AddThis',
			'ADmantX',
			'adressendeutschland',
			'Advanced Email Extractor v',
			'agentslug',
			'AHC',
			'aihit',
			'aiohttp\/',
			'Airmail',
			'akula\/',
			'alertra',
			'alexa site audit',
			'alyze\.info',
			'amagit',
			'AndroidDownloadManager',
			'Anemone',
			'Ant\.com',
			'Anturis Agent',
			'AnyEvent-HTTP\/',
			'Apache-HttpClient\/',
			'AportWorm\/[0-9]',
			'AppEngine-Google',
			'Arachmo',
			'arachnode',
			'Arachnophilia',
			'archive-com',
			'aria2',
			'asafaweb.com',
			'AskQuickly',
			'Astute',
			'autocite',
			'Autonomy',
			'B-l-i-t-z-B-O-T',
			'Backlink-Ceck\.de',
			'Bad-Neighborhood',
			'baidu\.com',
			'baypup\/[0-9]',
			'baypup\/colbert',
			'BazQux',
			'BCKLINKS',
			'BDFetch',
			'BegunAdvertising\/',
			'bibnum\.bnf',
			'BigBozz',
			'biglotron',
			'BingLocalSearch',
			'BingPreview',
			'binlar',
			'biz_Directory',
			'Blackboard Safeassign',
			'Bloglovin',
			'BlogPulseLive',
			'BlogSearch',
			'Blogtrottr',
			'boitho\.com-dc',
			'BPImageWalker',
			'Braintree-Webhooks',
			'Branch Metrics API',
			'Branch-Passthrough',
			'Browsershots',
			'BUbiNG',
			'Butterfly\/',
			'BuzzSumo',
			'CakePHP',
			'CapsuleChecker',
			'CaretNail',
			'cb crawl',
			'CC Metadata Scaper',
			'Cerberian Drtrs',
			'CERT\.at-Statistics-Survey',
			'cg-eye',
			'changedetection',
			'Charlotte',
			'CheckHost',
			'chkme\.com',
			'CirrusExplorer\/',
			'CISPA Vulnerability Notification',
			'CJNetworkQuality',
			'clips\.ua\.ac\.be',
			'Cloud mapping experiment',
			'CloudFlare-AlwaysOnline',
			'Cloudinary\/[0-9]',
			'cmcm\.com',
			'coccoc',
			'CommaFeed',
			'Commons-HttpClient',
			'Comodo SSL Checker',
			'contactbigdatafr',
			'convera',
			'copyright sheriff',
			'cosmos\/[0-9]',
			'Covario-IDS',
			'CrawlForMe\/[0-9]',
			'cron-job\.org',
			'Crowsnest',
			'curb',
			'Curious George',
			'curl',
			'cuwhois\/[0-9]',
			'CyberPatrol',
			'cybo\.com',
			'DareBoost',
			'DataparkSearch',
			'dataprovider',
			'Daum(oa)?[ \/][0-9]',
			'DeuSu',
			'developers\.google\.com\/\+\/web\/snippet\/',
			'Digg',
			'Dispatch\/',
			'dlvr',
			'DNS-Tools Header-Analyzer',
			'DNSPod-reporting',
			'docoloc',
			'DomainAppender',
			'dotSemantic',
			'downforeveryoneorjustme',
			'downnotifier\.com',
			'DowntimeDetector',
			'Dragonfly File Reader',
			'drupact',
			'Drupal (\+http:\/\/drupal\.org\/)',
			'dubaiindex',
			'EARTHCOM',
			'Easy-Thumb',
			'ec2linkfinder',
			'eCairn-Grabber',
			'ECCP',
			'ElectricMonk',
			'elefent',
			'EMail Exractor',
			'EmailWolf',
			'Embed PHP Library',
			'Embedly',
			'europarchive\.org',
			'evc-batch\/[0-9]',
			'EventMachine HttpClient',
			'Evidon',
			'Evrinid',
			'ExactSearch',
			'ExaleadCloudview',
			'Excel\/',
			'Exploratodo',
			'ezooms',
			'facebookexternalhit',
			'facebookplatform',
			'fairshare',
			'Faraday v',
			'Faveeo',
			'Favicon downloader',
			'FavOrg',
			'Feed Wrangler',
			'Feedbin',
			'FeedBooster',
			'FeedBucket',
			'FeedBurner',
			'FeedChecker',
			'Feedly',
			'Feedspot',
			'feeltiptop',
			'Fetch API',
			'Fetch\/[0-9]',
			'Fever\/[0-9]',
			'findlink',
			'findthatfile',
			'Flamingo_SearchEngine',
			'FlipboardBrowserProxy',
			'FlipboardProxy',
			'FlipboardRSS',
			'fluffy',
			'flynxapp',
			'forensiq',
			'FoundSeoTool\/[0-9]',
			'free thumbnails',
			'FreeWebMonitoring SiteChecker',
			'Funnelback',
			'g00g1e\.net',
			'GAChecker',
			'geek-tools',
			'Genderanalyzer',
			'Genieo',
			'GentleSource',
			'GetLinkInfo',
			'getprismatic\.com',
			'GetURLInfo\/[0-9]',
			'GigablastOpenSource',
			'Go [\d\.]* package http',
			'Go-http-client',
			'GomezAgent',
			'gooblog',
			'Goodzer\/[0-9]',
			'Google favicon',
			'Google Keyword Suggestion',
			'Google Keyword Tool',
			'Google Page Speed Insights',
			'Google PP Default',
			'Google Search Console',
			'Google Web Preview',
			'Google-Adwords',
			'Google-Apps-Script',
			'Google-Calendar-Importer',
			'Google-HTTP-Java-Client',
			'Google-Publisher-Plugin',
			'Google-SearchByImage',
			'Google-Site-Verification',
			'Google-Structured-Data-Testing-Tool',
			'google_partner_monitoring',
			'GoogleDocs',
			'GoogleHC\/',
			'GoogleProducer',
			'GoScraper',
			'GoSpotCheck',
			'GoSquared-Status-Checker',
			'gosquared-thumbnailer',
			'GotSiteMonitor',
			'Grammarly',
			'grouphigh',
			'grub-client',
			'GTmetrix',
			'Hatena',
			'hawkReader',
			'HEADMasterSEO',
			'HeartRails_Capture',
			'heritrix',
			'hledejLevne\.cz\/[0-9]',
			'Holmes',
			'HootSuite Image proxy',
			'Hootsuite-WebFeed\/[0-9]',
			'HostTracker',
			'ht:\/\/check',
			'htdig',
			'HTMLParser\/',
			'HTTP-Header-Abfrage',
			'http-kit',
			'HTTP-Tiny',
			'HTTP_Compression_Test',
			'http_request2',
			'http_requester',
			'HttpComponents',
			'httphr',
			'HTTPMon',
			'httpscheck',
			'httpssites_power',
			'httpunit',
			'HttpUrlConnection',
			'httrack',
			'hosterstats',
			'huaweisymantec',
			'HubPages.*crawlingpolicy',
			'HubSpot Connect',
			'HubSpot Marketing Grader',
			'HyperZbozi.cz Feeder',
			'ichiro',
			'IdeelaborPlagiaat',
			'IDG Twitter Links Resolver',
			'IDwhois\/[0-9]',
			'Iframely',
			'igdeSpyder',
			'IlTrovatore',
			'ImageEngine\/',
			'Imagga',
			'InAGist',
			'inbound\.li parser',
			'InDesign%20CC',
			'infegy',
			'infohelfer',
			'InfoWizards Reciprocal Link System PRO',
			'inpwrd\.com',
			'Integrity',
			'integromedb',
			'internet_archive',
			'InternetSeer',
			'internetVista monitor',
			'IODC',
			'IOI',
			'ips-agent',
			'iqdb\/',
			'Irokez',
			'isitup\.org',
			'iskanie',
			'iZSearch',
			'janforman',
			'Jigsaw',
			'Jobboerse',
			'jobo',
			'Jobrapido',
			'KeepRight OpenStreetMap Checker',
			'KimonoLabs\/',
			'knows\.is',
			'kouio',
			'KrOWLer',
			'kulturarw3',
			'KumKie',
			'L\.webis',
			'Larbin',
			'LayeredExtractor',
			'LibVLC',
			'libwww',
			'link checker',
			'Link Valet',
			'link_thumbnailer',
			'linkCheck',
			'linkdex',
			'LinkExaminer',
			'linkfluence',
			'linkpeek',
			'LinkTiger',
			'LinkWalker',
			'Lipperhey',
			'livedoor ScreenShot',
			'LoadImpactPageAnalyzer',
			'LoadImpactRload',
			'LongURL API',
			'looksystems\.net',
			'ltx71',
			'lwp-trivial',
			'lycos',
			'LYT\.SR',
			'mabontland',
			'MagpieRSS',
			'Mail.Ru',
			'MailChimp\.com',
			'Mandrill',
			'marketinggrader',
			'Mediapartners-Google',
			'MegaIndex\.ru',
			'Melvil Rawi\/',
			'MergeFlow-PageReader',
			'MetaInspector',
			'Metaspinner',
			'MetaURI',
			'Microsearch',
			'Microsoft Office ',
			'Microsoft Windows Network Diagnostics',
			'Mindjet',
			'Miniflux',
			'Mnogosearch',
			'mogimogi',
			'Mojolicious (Perl)',
			'monitis',
			'Monitority\/[0-9]',
			'montastic',
			'MonTools',
			'Moreover',
			'Morning Paper',
			'mowser',
			'Mrcgiguy',
			'mShots',
			'MVAClient',
			'nagios',
			'Najdi\.si\/',
			'NETCRAFT',
			'NetLyzer FastProbe',
			'netresearch',
			'NetShelter ContentScan',
			'NetTrack',
			'Netvibes',
			'Neustar WPM',
			'NeutrinoAPI',
			'NewsBlur .*Finder',
			'NewsGator',
			'newsme',
			'newspaper\/',
			'NG-Search',
			'nineconnections\.com',
			'NLNZ_IAHarvester',
			'Nmap Scripting Engine',
			'node-superagent',
			'node\.io',
			'nominet\.org\.uk',
			'Norton-Safeweb',
			'Notifixious',
			'notifyninja',
			'nuhk',
			'nutch',
			'Nuzzel',
			'nWormFeedFinder',
			'Nymesis',
			'Ocelli\/[0-9]',
			'oegp',
			'okhttp',
			'Omea Reader',
			'omgili',
			'Online Domain Tools',
			'OpenCalaisSemanticProxy',
			'Openstat\/',
			'OpenVAS',
			'Optimizer',
			'Orbiter',
			'OrgProbe\/[0-9]',
			'ow\.ly',
			'ownCloud News',
			'Page Analyzer',
			'Page Valet',
			'page2rss',
			'page_verifier',
			'PagePeeker',
			'Pagespeed\/[0-9]',
			'Panopta',
			'panscient',
			'parsijoo',
			'PayPal IPN',
			'Pcore-HTTP',
			'Pearltrees',
			'peerindex',
			'Peew',
			'PhantomJS\/',
			'Photon\/',
			'phpcrawl',
			'phpservermon',
			'Pi-Monster',
			'Pingdom\.com',
			'Pingoscope',
			'PingSpot',
			'Pinterest',
			'Pizilla',
			'Ploetz \+ Zeller',
			'Plukkie',
			'PocketParser',
			'Pompos',
			'Porkbun',
			'Port Monitor',
			'postano',
			'PostPost',
			'postrank',
			'PowerPoint\/',
			'Priceonomics Analysis Engine',
			'Prlog',
			'probethenet',
			'Project 25499',
			'Promotion_Tools_www.searchenginepromotionhelp.com',
			'prospectb2b',
			'Protopage',
			'proximic',
			'PTST ',
			'PTST\/[0-9]+',
			'Pulsepoint XT3 web scraper',
			'Python-httplib2',
			'python-requests',
			'Python-urllib',
			'Qirina Hurdler',
			'Qseero',
			'Qualidator.com SiteAnalyzer',
			'Quora Link Preview',
			'Qwantify',
			'Radian6',
			'RankSonicSiteAuditor',
			'Readability',
			'RealPlayer%20Downloader',
			'RebelMouse',
			'redback\/',
			'Redirect Checker Tool',
			'ReederForMac',
			'ResponseCodeTest\/[0-9]',
			'RestSharp',
			'RetrevoPageAnalyzer',
			'Riddler',
			'Rival IQ',
			'Robosourcer',
			'Robozilla\/[0-9]',
			'ROI Hunter',
			'SalesIntelligent',
			'SauceNAO',
			'SBIder',
			'Scoop',
			'scooter',
			'ScoutJet',
			'ScoutURLMonitor',
			'Scrapy',
			'Scrubby',
			'SearchSight',
			'semanticdiscovery',
			'semanticjuice',
			'SEO Browser',
			'Seo Servis',
			'seo-nastroj.cz',
			'Seobility',
			'SEOCentro',
			'SeoCheck',
			'SeopultContentAnalyzer',
			'SEOstats',
			'Server Density Service Monitoring',
			'servernfo\.com',
			'Seznam screenshot-generator',
			'Shelob',
			'Shoppimon Analyzer',
			'ShoppimonAgent\/[0-9]',
			'ShopWiki',
			'ShortLinkTranslate',
			'shrinktheweb',
			'SilverReader',
			'SimplePie',
			'SimplyFast',
			'Site-Shot\/',
			'Site24x7',
			'SiteBar',
			'SiteCondor',
			'siteexplorer\.info',
			'SiteGuardian',
			'Siteimprove\.com',
			'Sitemap(s)? Generator',
			'Siteshooter B0t',
			'SiteTruth',
			'sitexy\.com',
			'SkypeUriPreview',
			'slider\.com',
			'slurp',
			'SMRF URL Expander',
			'Snappy',
			'SniffRSS',
			'sniptracker',
			'Snoopy',
			'sogou web',
			'SortSite',
			'spaziodati',
			'Specificfeeds',
			'speedy',
			'SPEng',
			'Spinn3r',
			'spray-can',
			'Sprinklr ',
			'spyonweb',
			'Sqworm',
			'SSL Labs',
			'StackRambler',
			'Statastico\/',
			'StatusCake',
			'Stratagems Kumo',
			'Stroke.cz',
			'StudioFACA',
			'suchen',
			'summify',
			'Super Monitoring',
			'Surphace Scout',
			'SwiteScraper',
			'Symfony2 BrowserKit',
			'Sysomos',
			'T0PHackTeam',
			'Tarantula\/',
			'teoma',
			'terrainformatica\.com',
			'The Expert HTML Source Viewer',
			'theinternetrules',
			'theoldreader\.com',
			'Thumbshots',
			'ThumbSniper',
			'TinEye',
			'Tiny Tiny RSS',
			'topster',
			'touche.com',
			'Traackr.com',
			'truwoGPS',
			'tweetedtimes\.com',
			'Tweetminster',
			'Twikle',
			'Twingly',
			'Typhoeus',
			'ubermetrics-technologies',
			'uclassify',
			'UdmSearch',
			'UnwindFetchor',
			'updated',
			'Upflow',
			'URLChecker',
			'URLitor.com',
			'urlresolver',
			'Urlstat',
			'UrlTrends Ranking Updater',
			'Vagabondo',
			'via ggpht\.com GoogleImageProxy',
			'visionutils',
			'vkShare',
			'voltron',
			'Vortex\/[0-9]',
			'voyager\/',
			'VSAgent\/[0-9]',
			'VSB-TUO\/[0-9]',
			'VYU2',
			'w3af\.org',
			'W3C-checklink',
			'W3C-mobileOK',
			'W3C_I18n-Checker',
			'W3C_Unicorn',
			'wangling',
			'Wappalyzer',
			'WatchMouse',
			'WbSrch\/',
			'web-capture\.net',
			'Web-Monitoring',
			'Web-sniffer',
			'Webauskunft',
			'WebCapture',
			'webcollage',
			'WebCookies',
			'WebCorp',
			'WebDoc',
			'WebFetch',
			'WebImages',
			'WebIndex',
			'webkit2png',
			'webmastercoffee',
			'webmon ',
			'webscreenie',
			'Webshot',
			'Website Analyzer\/',
			'websitepulse[+ ]checker',
			'Websnapr\/',
			'Websquash\.com',
			'Webthumb\/[0-9]',
			'WebThumbnail',
			'WeCrawlForThePeace',
			'WeLikeLinks',
			'WEPA',
			'WeSEE',
			'wf84',
			'wget',
			'WhatsApp',
			'WhatsMyIP',
			'WhatWeb',
			'Whibse',
			'Whynder Magnet',
			'Windows-RSS-Platform',
			'WinHttpRequest',
			'wkhtmlto',
			'wmtips',
			'Woko',
			'WomlpeFactory',
			'Word\/',
			'WordPress\/',
			'wotbox',
			'WP Engine Install Performance API',
			'WPScan',
			'wscheck',
			'WWW-Mechanize',
			'www\.monitor\.us',
			'XaxisSemanticsClassifier',
			'Xenu Link Sleuth',
			'XING-contenttabreceiver\/[0-9]',
			'XmlSitemapGenerator',
			'xpymep([0-9]?)\.exe',
			'Y!J-(ASR|BSC)',
			'Yaanb',
			'yacy',
			'Yahoo Ad monitoring',
			'Yahoo Link Preview',
			'YahooCacheSystem',
			'YahooSeeker',
			'YahooYSMcm',
			'YandeG',
			'yandex',
			'yanga',
			'yeti',
			'Yo-yo',
			'Yoleo Consumer',
			'yoogliFetchAgent',
			'YottaaMonitor',
			'yourls\.org',
			'Zao',
			'Zemanta Aggregator',
			'Zend\\\\Http\\\\Client',
			'Zend_Http_Client',
			'zgrab',
			'ZnajdzFoto',
			'ZyBorg',
			'[a-z0-9\-_]*((?<!cu)bot|crawler|archiver|transcoder|spider|uptime|validator|fetcher)',
		);

		return $data;
	}

	/**
	 * Return the list of strings to remove from the user agent before running the crawler regex.
	 *
	 * @return array
	 */
	public function get_exclusions_list() {
		$data = array(
			'Safari.[\d\.]*',
			'Firefox.[\d\.]*',
			'Chrome.[\d\.]*',
			'Chromium.[\d\.]*',
			'MSIE.[\d\.]',
			'Opera\/[\d\.]*',
			'Mozilla.[\d\.]*',
			'AppleWebKit.[\d\.]*',
			'Trident.[\d\.]*',
			'Windows NT.[\d\.]*',
			'Android [\d\.]*',
			'Macintosh.',
			'Ubuntu',
			'Linux',
			'[ ]Intel',
			'Mac OS X [\d_]*',
			'(like )?Gecko(.[\d\.]*)?',
			'KHTML,',
			'CriOS.[\d\.]*',
			'CPU iPhone OS ([0-9_])* like Mac OS X',
			'CPU OS ([0-9_])* like Mac OS X',
			'iPod',
			'compatible',
			'x86_..',
			'i686',
			'x64',
			'X11',
			'rv:[\d\.]*',
			'Version.[\d\.]*',
			'WOW64',
			'Win64',
			'Dalvik.[\d\.]*',
			' \.NET CLR [\d\.]*',
			'Presto.[\d\.]*',
			'Media Center PC',
			'BlackBerry',
			'Build',
			'Opera Mini\/\d{1,2}\.\d{1,2}\.[\d\.]*\/\d{1,2}\.',
			'Opera',
			' \.NET[\d\.]*',
			'\(|\)|;|,', // remove the following characters ( ) : ,
		);

		return $data;
	}

	/**
	 * Return all possible HTTP headers that represent the User-Agent string.
	 *
	 * @return array
	 */
	public function get_headers_list() {
		$data = array(
			// the default User-Agent string.
			'HTTP_USER_AGENT',
			// header can occur on devices using Opera Mini.
			'HTTP_X_OPERAMINI_PHONE_UA',
			// vodafone specific header: http://www.seoprinciple.com/mobile-web-community-still-angry-at-vodafone/24/
			'HTTP_X_DEVICE_USER_AGENT',
			'HTTP_X_ORIGINAL_USER_AGENT',
			'HTTP_X_SKYFIRE_PHONE',
			'HTTP_X_BOLT_PHONE_UA',
			'HTTP_DEVICE_STOCK_UA',
			'HTTP_X_UCBROWSER_DEVICE_UA',
			// sometimes, bots (especially Google) use a genuine user agent, but fill this header in with their email address
			'HTTP_FROM',
		);

		return $data;
	}

}
