<?php

namespace MediaWiki\Extension\RawArgs;

use MediaWiki\Extension\Scribunto\Engines\LuaCommon\LibraryBase;
use MediaWiki\Parser\PPFrame;
use MediaWiki\Parser\PPTemplateFrame_Hash;

/**
 * Exposes raw, unexpanded and unstripped wikitext of the arguments to Scribunto
 * modules.
 */
class RawArgsLibrary extends LibraryBase {

	/**
	 * Memoized map of raw #invoke arguments, keyed by frame ID.
	 *
	 * @var array{int,array}
	 */
	private static $argumentCache = [];

	/**
	 * Memoized map of raw #invoke parent arguments, keyed by frame ID.
	 *
	 * @var array{int,array}
	 */
	private static $parentArgumentCache = [];

	/** @inheritDoc */
	public function register() {
		return $this->getEngine()->registerInterface(
			__DIR__ . '/mw.ext.rawArgs.lua',
			[
				'get' => [ $this, 'get' ],
				'getParent' => [ $this, 'getParent' ],
			],
			[],
		);
	}

	/**
	 * Handler for mw.ext.rawArgs.get()
	 *
	 * @return array[]
	 */
	public function get() {
		[ $frameId, $frame, $args ] = Hooks::getCurrentInvokeContext();
		if ( isset( self::$argumentCache[$frameId] ) ) {
			return [ self::$argumentCache[$frameId] ];
		}
		$items = $args;
		unset( $items[0] );
		unset( $items[1] );

		$raw = [];
		foreach ( $items as $arg ) {
			$bits = $arg->splitArg();
			if ( $bits['index'] !== '' ) {
				$index = (int)$bits['index'] - 1;
				$raw[$index] = $frame->expand( $bits['value'], PPFrame::RECOVER_ORIG );
			} else {
				$name = trim( $frame->expand( $bits['name'], PPFrame::STRIP_COMMENTS ) );
				$raw[$name] = trim( $frame->expand( $bits['value'], PPFrame::RECOVER_ORIG ) );
			}
		}
		self::$argumentCache[$frameId] = $raw;
		return [ $raw ];
	}

	/**
	 * Handler for mw.ext.rawArgs.getParent()
	 *
	 * @return array[]
	 */
	public function getParent() {
		[ $frameId, $frame ] = Hooks::getCurrentInvokeContext();
		if ( isset( self::$parentArgumentCache[$frameId] ) ) {
			return [ self::$parentArgumentCache[$frameId] ];
		}
		if ( !( $frame instanceof PPTemplateFrame_Hash ) ) {
			return [];
		}
		$raw = [];
		foreach ( $frame->numberedArgs as $index => $value ) {
			$raw[$index] = $frame->expand( $value, PPFrame::RECOVER_ORIG );
		}
		foreach ( $frame->namedArgs as $name => $value ) {
			$raw[$name] = trim( $frame->expand( $value, PPFrame::RECOVER_ORIG ) );
		}
		self::$parentArgumentCache[$frameId] = $raw;
		return [ $raw ];
	}
}
