<?php

namespace MediaWiki\Extension\RawArgs;

use MediaWiki\Extension\Scribunto\Hooks\ScribuntoExternalLibrariesHook;
use MediaWiki\Hook\ParserFirstCallInitHook;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;
use MediaWiki\Parser\PPNode;
use RuntimeException;

/**
 * Wraps Scribunto's {{#invoke:}} parser function so that, for the duration of
 * each invocation, the raw (unexpanded, unstripped) wikitext of every
 * argument is available to a Lua-side library (see {@link RawArgsLibrary}).
 */
class Hooks implements ParserFirstCallInitHook, ScribuntoExternalLibrariesHook {

	/**
	 * Original 'invoke' callback, keyed by spl_object_id(Parser)
	 *
	 * @var array<int,callable>
	 */
	private static array $originalInvoke = [];

	/**
	 * Stack of parent frames and arguments for currently executing #invoke
	 * calls.
	 *
	 * @var array<int,array{int, PPFrame, PPNode[]}>
	 */
	private static array $invokeStack = [];

	/**
	 * Count of all frame-argument pairs placed on $invokeStack.
	 *
	 * @var int
	 */
	private static int $frameCounter = 0;

	/** @inheritDoc */
	public function onParserFirstCallInit( $parser ) {
		$old = $parser->setFunctionHook( 'invoke', self::invokeWrapper( ... ), Parser::SFH_OBJECT_ARGS );
		if ( $old !== null ) {
			self::$originalInvoke[ spl_object_id( $parser ) ] = $old;
		}
	}

	/** @inheritDoc */
	public function onScribuntoExternalLibraries( string $engine, array &$extraLibraries ) {
		if ( $engine === 'lua' ) {
			$extraLibraries['mw.ext.rawArgs'] = RawArgsLibrary::class;
		}
	}

	/**
	 * Replacement 'invoke' parser function callback. Records the raw wikitext
	 * of the call's arguments, then delegates to Scribunto's original handler.
	 *
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @param PPNode[] $args
	 * @return string|array
	 */
	public static function invokeWrapper( Parser $parser, PPFrame $frame, array $args ) {
		$original = self::$originalInvoke[ spl_object_id( $parser ) ] ?? null;
		if ( $original === null ) {
			throw new RuntimeException(
				'RawArgs: no #invoke handler was registered by Scribunto to wrap'
			);
		}

		self::$invokeStack[] = [ self::$frameCounter++, $frame, $args ];
		try {
			return $original( $parser, $frame, $args );
		} finally {
			array_pop( self::$invokeStack );
		}
	}

	/**
	 * Get the current #invoke's parent frame and arguments, for use by
	 * RawArgsLibrary.
	 *
	 * @return array{int, PPFrame, PPNode[]}
	 */
	public static function getCurrentInvokeContext(): array {
		return end( self::$invokeStack );
	}
}
