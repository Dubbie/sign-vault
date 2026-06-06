function quoteFiles(files) {
  return files.map((file) => `"${file}"`).join(' ')
}

function stripPrefix(files, prefix) {
  return files
    .filter((file) => file.startsWith(prefix))
    .map((file) => file.slice(prefix.length))
}

export default {
  '*.{md,json,yml,yaml,webmanifest}': (files) => {
    return [`prettier --write --ignore-unknown ${quoteFiles(files)}`]
  },
  'frontend/**/*.{js,ts,vue,css}': (files) => {
    const workspaceFiles = stripPrefix(files, 'frontend/')

    if (workspaceFiles.length === 0) {
      return []
    }

    const quotedFiles = quoteFiles(workspaceFiles)

    return [
      `npm exec --workspace frontend -- prettier --write --ignore-unknown ${quotedFiles}`,
      `npm exec --workspace frontend -- eslint --fix --cache --no-warn-ignored ${quotedFiles}`,
      `npm exec --workspace frontend -- oxlint --fix ${quotedFiles}`,
    ]
  },
  'landing/**/*.{js,ts,vue,css}': (files) => {
    const workspaceFiles = stripPrefix(files, 'landing/')

    if (workspaceFiles.length === 0) {
      return []
    }

    return [`npm exec --workspace landing -- prettier --write --ignore-unknown ${quoteFiles(workspaceFiles)}`]
  },
}
