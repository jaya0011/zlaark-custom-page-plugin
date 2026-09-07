# Plugin Analysis Tools

This directory contains analysis and diagnostic tools for the Custom Page Builder plugin. These are **development tools**, not WordPress plugins.

## Tools Available

### 1. comprehensive-analysis.php
- **Purpose**: Performs thorough analysis of all plugin files
- **Features**: 
  - Plugin file discovery
  - Individual plugin analysis
  - Dependency analysis
  - Activation success prediction
- **Usage**: Run via web browser or command line

### 2. deep-analysis.php
- **Purpose**: Deep dive analysis of plugin activation issues
- **Features**:
  - Complete plugin file inventory
  - Detailed error analysis
  - Activation prediction with reasoning
  - Specific code analysis for failed plugins
- **Usage**: Run via web browser for detailed reports

### 3. simple-analysis.php
- **Purpose**: Quick overview of plugin files and basic issues
- **Features**:
  - Plugin file listing
  - Syntax checking
  - Basic activation analysis
  - Simple recommendations
- **Usage**: Run for quick diagnostics

### 4. activation-diagnostic.php
- **Purpose**: Diagnose why multiple plugin instances won't activate
- **Features**:
  - Plugin instance analysis
  - File conflict detection
  - Common issues identification
  - Step-by-step fix recommendations
- **Usage**: Best run from within WordPress environment

### 5. emergency-fix.php
- **Purpose**: Creates emergency working versions of plugins
- **Features**:
  - Generates bulletproof plugin code
  - Provides immediate solutions
  - Creates safe working versions
- **Usage**: Run when plugins fail to activate

## Important Notes

- These tools are **NOT WordPress plugins** - they don't have plugin headers
- They are development/diagnostic tools only
- Run them via web browser or command line
- They analyze the parent directory for plugin files
- Safe to use - they only analyze, don't modify plugin files (except emergency-fix.php)

## Usage Instructions

1. **Via Web Browser**: Upload to your WordPress installation and access directly
2. **Via Command Line**: Run with `php filename.php`
3. **For WordPress Integration**: Use activation-diagnostic.php from WordPress root

## Security

- These tools are for development use only
- Remove from production environments
- They contain analysis code that could expose system information