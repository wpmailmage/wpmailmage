<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitd2737b5bbba7e829ff350cc8953a7c90
{
    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'Symfony\\Component\\CssSelector\\' => 30,
        ),
        'P' => 
        array (
            'Pelago\\' => 7,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Symfony\\Component\\CssSelector\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/css-selector',
        ),
        'Pelago\\' => 
        array (
            0 => __DIR__ . '/..' . '/pelago/emogrifier/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'Pelago\\Emogrifier' => __DIR__ . '/..' . '/pelago/emogrifier/src/Emogrifier.php',
        'Pelago\\Emogrifier\\CssInliner' => __DIR__ . '/..' . '/pelago/emogrifier/src/Emogrifier/CssInliner.php',
        'Pelago\\Emogrifier\\HtmlProcessor\\AbstractHtmlProcessor' => __DIR__ . '/..' . '/pelago/emogrifier/src/Emogrifier/HtmlProcessor/AbstractHtmlProcessor.php',
        'Pelago\\Emogrifier\\HtmlProcessor\\CssToAttributeConverter' => __DIR__ . '/..' . '/pelago/emogrifier/src/Emogrifier/HtmlProcessor/CssToAttributeConverter.php',
        'Pelago\\Emogrifier\\HtmlProcessor\\HtmlNormalizer' => __DIR__ . '/..' . '/pelago/emogrifier/src/Emogrifier/HtmlProcessor/HtmlNormalizer.php',
        'Pelago\\Emogrifier\\HtmlProcessor\\HtmlPruner' => __DIR__ . '/..' . '/pelago/emogrifier/src/Emogrifier/HtmlProcessor/HtmlPruner.php',
        'Pelago\\Emogrifier\\Utilities\\ArrayIntersector' => __DIR__ . '/..' . '/pelago/emogrifier/src/Emogrifier/Utilities/ArrayIntersector.php',
        'Pelago\\Emogrifier\\Utilities\\CssConcatenator' => __DIR__ . '/..' . '/pelago/emogrifier/src/Emogrifier/Utilities/CssConcatenator.php',
        'Symfony\\Component\\CssSelector\\CssSelectorConverter' => __DIR__ . '/..' . '/symfony/css-selector/CssSelectorConverter.php',
        'Symfony\\Component\\CssSelector\\Exception\\ExceptionInterface' => __DIR__ . '/..' . '/symfony/css-selector/Exception/ExceptionInterface.php',
        'Symfony\\Component\\CssSelector\\Exception\\ExpressionErrorException' => __DIR__ . '/..' . '/symfony/css-selector/Exception/ExpressionErrorException.php',
        'Symfony\\Component\\CssSelector\\Exception\\InternalErrorException' => __DIR__ . '/..' . '/symfony/css-selector/Exception/InternalErrorException.php',
        'Symfony\\Component\\CssSelector\\Exception\\ParseException' => __DIR__ . '/..' . '/symfony/css-selector/Exception/ParseException.php',
        'Symfony\\Component\\CssSelector\\Exception\\SyntaxErrorException' => __DIR__ . '/..' . '/symfony/css-selector/Exception/SyntaxErrorException.php',
        'Symfony\\Component\\CssSelector\\Node\\AbstractNode' => __DIR__ . '/..' . '/symfony/css-selector/Node/AbstractNode.php',
        'Symfony\\Component\\CssSelector\\Node\\AttributeNode' => __DIR__ . '/..' . '/symfony/css-selector/Node/AttributeNode.php',
        'Symfony\\Component\\CssSelector\\Node\\ClassNode' => __DIR__ . '/..' . '/symfony/css-selector/Node/ClassNode.php',
        'Symfony\\Component\\CssSelector\\Node\\CombinedSelectorNode' => __DIR__ . '/..' . '/symfony/css-selector/Node/CombinedSelectorNode.php',
        'Symfony\\Component\\CssSelector\\Node\\ElementNode' => __DIR__ . '/..' . '/symfony/css-selector/Node/ElementNode.php',
        'Symfony\\Component\\CssSelector\\Node\\FunctionNode' => __DIR__ . '/..' . '/symfony/css-selector/Node/FunctionNode.php',
        'Symfony\\Component\\CssSelector\\Node\\HashNode' => __DIR__ . '/..' . '/symfony/css-selector/Node/HashNode.php',
        'Symfony\\Component\\CssSelector\\Node\\NegationNode' => __DIR__ . '/..' . '/symfony/css-selector/Node/NegationNode.php',
        'Symfony\\Component\\CssSelector\\Node\\NodeInterface' => __DIR__ . '/..' . '/symfony/css-selector/Node/NodeInterface.php',
        'Symfony\\Component\\CssSelector\\Node\\PseudoNode' => __DIR__ . '/..' . '/symfony/css-selector/Node/PseudoNode.php',
        'Symfony\\Component\\CssSelector\\Node\\SelectorNode' => __DIR__ . '/..' . '/symfony/css-selector/Node/SelectorNode.php',
        'Symfony\\Component\\CssSelector\\Node\\Specificity' => __DIR__ . '/..' . '/symfony/css-selector/Node/Specificity.php',
        'Symfony\\Component\\CssSelector\\Parser\\Handler\\CommentHandler' => __DIR__ . '/..' . '/symfony/css-selector/Parser/Handler/CommentHandler.php',
        'Symfony\\Component\\CssSelector\\Parser\\Handler\\HandlerInterface' => __DIR__ . '/..' . '/symfony/css-selector/Parser/Handler/HandlerInterface.php',
        'Symfony\\Component\\CssSelector\\Parser\\Handler\\HashHandler' => __DIR__ . '/..' . '/symfony/css-selector/Parser/Handler/HashHandler.php',
        'Symfony\\Component\\CssSelector\\Parser\\Handler\\IdentifierHandler' => __DIR__ . '/..' . '/symfony/css-selector/Parser/Handler/IdentifierHandler.php',
        'Symfony\\Component\\CssSelector\\Parser\\Handler\\NumberHandler' => __DIR__ . '/..' . '/symfony/css-selector/Parser/Handler/NumberHandler.php',
        'Symfony\\Component\\CssSelector\\Parser\\Handler\\StringHandler' => __DIR__ . '/..' . '/symfony/css-selector/Parser/Handler/StringHandler.php',
        'Symfony\\Component\\CssSelector\\Parser\\Handler\\WhitespaceHandler' => __DIR__ . '/..' . '/symfony/css-selector/Parser/Handler/WhitespaceHandler.php',
        'Symfony\\Component\\CssSelector\\Parser\\Parser' => __DIR__ . '/..' . '/symfony/css-selector/Parser/Parser.php',
        'Symfony\\Component\\CssSelector\\Parser\\ParserInterface' => __DIR__ . '/..' . '/symfony/css-selector/Parser/ParserInterface.php',
        'Symfony\\Component\\CssSelector\\Parser\\Reader' => __DIR__ . '/..' . '/symfony/css-selector/Parser/Reader.php',
        'Symfony\\Component\\CssSelector\\Parser\\Shortcut\\ClassParser' => __DIR__ . '/..' . '/symfony/css-selector/Parser/Shortcut/ClassParser.php',
        'Symfony\\Component\\CssSelector\\Parser\\Shortcut\\ElementParser' => __DIR__ . '/..' . '/symfony/css-selector/Parser/Shortcut/ElementParser.php',
        'Symfony\\Component\\CssSelector\\Parser\\Shortcut\\EmptyStringParser' => __DIR__ . '/..' . '/symfony/css-selector/Parser/Shortcut/EmptyStringParser.php',
        'Symfony\\Component\\CssSelector\\Parser\\Shortcut\\HashParser' => __DIR__ . '/..' . '/symfony/css-selector/Parser/Shortcut/HashParser.php',
        'Symfony\\Component\\CssSelector\\Parser\\Token' => __DIR__ . '/..' . '/symfony/css-selector/Parser/Token.php',
        'Symfony\\Component\\CssSelector\\Parser\\TokenStream' => __DIR__ . '/..' . '/symfony/css-selector/Parser/TokenStream.php',
        'Symfony\\Component\\CssSelector\\Parser\\Tokenizer\\Tokenizer' => __DIR__ . '/..' . '/symfony/css-selector/Parser/Tokenizer/Tokenizer.php',
        'Symfony\\Component\\CssSelector\\Parser\\Tokenizer\\TokenizerEscaping' => __DIR__ . '/..' . '/symfony/css-selector/Parser/Tokenizer/TokenizerEscaping.php',
        'Symfony\\Component\\CssSelector\\Parser\\Tokenizer\\TokenizerPatterns' => __DIR__ . '/..' . '/symfony/css-selector/Parser/Tokenizer/TokenizerPatterns.php',
        'Symfony\\Component\\CssSelector\\XPath\\Extension\\AbstractExtension' => __DIR__ . '/..' . '/symfony/css-selector/XPath/Extension/AbstractExtension.php',
        'Symfony\\Component\\CssSelector\\XPath\\Extension\\AttributeMatchingExtension' => __DIR__ . '/..' . '/symfony/css-selector/XPath/Extension/AttributeMatchingExtension.php',
        'Symfony\\Component\\CssSelector\\XPath\\Extension\\CombinationExtension' => __DIR__ . '/..' . '/symfony/css-selector/XPath/Extension/CombinationExtension.php',
        'Symfony\\Component\\CssSelector\\XPath\\Extension\\ExtensionInterface' => __DIR__ . '/..' . '/symfony/css-selector/XPath/Extension/ExtensionInterface.php',
        'Symfony\\Component\\CssSelector\\XPath\\Extension\\FunctionExtension' => __DIR__ . '/..' . '/symfony/css-selector/XPath/Extension/FunctionExtension.php',
        'Symfony\\Component\\CssSelector\\XPath\\Extension\\HtmlExtension' => __DIR__ . '/..' . '/symfony/css-selector/XPath/Extension/HtmlExtension.php',
        'Symfony\\Component\\CssSelector\\XPath\\Extension\\NodeExtension' => __DIR__ . '/..' . '/symfony/css-selector/XPath/Extension/NodeExtension.php',
        'Symfony\\Component\\CssSelector\\XPath\\Extension\\PseudoClassExtension' => __DIR__ . '/..' . '/symfony/css-selector/XPath/Extension/PseudoClassExtension.php',
        'Symfony\\Component\\CssSelector\\XPath\\Translator' => __DIR__ . '/..' . '/symfony/css-selector/XPath/Translator.php',
        'Symfony\\Component\\CssSelector\\XPath\\TranslatorInterface' => __DIR__ . '/..' . '/symfony/css-selector/XPath/TranslatorInterface.php',
        'Symfony\\Component\\CssSelector\\XPath\\XPathExpr' => __DIR__ . '/..' . '/symfony/css-selector/XPath/XPathExpr.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitd2737b5bbba7e829ff350cc8953a7c90::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitd2737b5bbba7e829ff350cc8953a7c90::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitd2737b5bbba7e829ff350cc8953a7c90::$classMap;

        }, null, ClassLoader::class);
    }
}
